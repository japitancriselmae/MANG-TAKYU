<?php

namespace App\Http\Controllers;

use App\Models\CookingBatch;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\CookingBatchNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->employee?->role?->role_name;
        $filter = request()->query('filter');
        $filter = in_array($filter, ['pending', 'fresh-chicken'], true)
            ? $filter
            : 'all';

        $query = Order::with([
            'employee',
            'orderItems.product',
            'payments',
        ])
            ->orderByDesc('order_date')
            ->orderByDesc('order_id');

        if ($role !== 'Manager') {
            $query->where('employee_id', $user->employee_id);
        }

        if ($filter === 'pending') {
            $query->where('status', 'Pending');
        }

        if ($filter === 'fresh-chicken') {
            $query->where('is_fresh_chicken', true)
                ->whereIn('status', ['Pending', 'Confirmed', 'Preparing', 'Ready']);
        }

        $orders = $query->get();
        $requestedOrderId = request()->integer('status_order');
        $autoOpenOrderId = $orders->firstWhere('order_id', $requestedOrderId)?->order_id;

        $pendingOrdersCount = Order::query()
            ->when($role !== 'Manager', fn ($orderQuery) => $orderQuery->where('employee_id', $user->employee_id))
            ->where('status', 'Pending')
            ->count();

        return view('orders.index', compact('orders', 'filter', 'pendingOrdersCount', 'autoOpenOrderId'));
    }

    public function create()
    {
        $products = Product::where('status', 'Active')
            ->orderBy('product_name')
            ->get();

        $chickenBatches = CookingBatch::query()
            ->where('food_type', 'chicken')
            ->where('status', 'cooking')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();

        return view('orders.create', compact('chickenBatches', 'products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_type' => [
                'required',
                'in:Dine-in,Take-out',
            ],

            'is_fresh_chicken' => [
                'nullable',
                'boolean',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'exists:products,product_id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'payment_method' => [
                'required',
                'in:Cash,GCash',
            ],

            'amount_paid' => [
                'required',
                'numeric',
                'min:0',
            ],

            'gcash_proof' => [
                'required_if:payment_method,GCash',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $employeeId = auth()->user()->employee_id;
        $amountPaid = (float) $validated['amount_paid'];
        $paymentMethod = $validated['payment_method'];

        if (! $employeeId) {
            return back()
                ->withErrors([
                    'order' => 'Your account is not linked to an employee.',
                ])
                ->withInput();
        }

        $isFreshChicken =
            $request->boolean('is_fresh_chicken');

        $proofPath = null;

        try {
            $order = DB::transaction(function () use (
                $validated,
                $employeeId,
                $isFreshChicken,
                $amountPaid,
                $paymentMethod,
                $request,
                &$proofPath,
            ) {
                $order = Order::create([
                    'employee_id' => $employeeId,
                    'order_date' => now(),
                    'order_type' => $validated['order_type'],
                    'is_fresh_chicken' => $isFreshChicken,
                    'estimated_ready_at' => null,
                    'ready_at' => null,
                    'total_amount' => 0,
                    'status' => 'Pending',
                ]);

                $totalAmount = 0;

                foreach ($validated['items'] as $item) {
                    $product = Product::query()
                        ->where('product_id', $item['product_id'])
                        ->where('status', 'Active')
                        ->lockForUpdate()
                        ->firstOrFail();

                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $product->price;
                    $subtotal = $unitPrice * $quantity;

                    OrderItem::create([
                        'order_id' => $order->order_id,
                        'product_id' => $product->product_id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);

                    $totalAmount += $subtotal;
                }

                if ($amountPaid < $totalAmount) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'Payment is insufficient. Order total is ₱'.number_format($totalAmount, 2).'.',
                    ]);
                }

                if (
                    $paymentMethod === 'GCash'
                    && round($amountPaid * 100) !== round($totalAmount * 100)
                ) {
                    throw ValidationException::withMessages([
                        'amount_paid' => 'GCash payment must match the order total exactly.',
                    ]);
                }

                $order->update([
                    'total_amount' => $totalAmount,
                ]);

                $inventoryError = $this->deductOrderIngredients($order);

                if ($inventoryError !== null) {
                    throw ValidationException::withMessages([
                        'order' => $inventoryError,
                    ]);
                }

                $hasReadyChickenBatch = $isFreshChicken
                    && CookingBatch::query()
                        ->where('food_type', 'chicken')
                        ->where('status', 'ready')
                        ->exists();

                $order->update([
                    'status' => ! $isFreshChicken
                        ? 'Completed'
                        : ($hasReadyChickenBatch ? 'Ready' : 'Pending'),
                    'ready_at' => $hasReadyChickenBatch ? now() : null,
                ]);

                if ($paymentMethod === 'GCash') {
                    $proofPath = $request->file('gcash_proof')?->store('gcash-proofs', 'local');

                    if (! $proofPath) {
                        throw new RuntimeException('The GCash payment proof could not be saved.');
                    }
                }

                Payment::create([
                    'order_id' => $order->order_id,
                    'payment_method' => $paymentMethod,
                    'amount_paid' => $amountPaid,
                    'change_amount' => $amountPaid - $totalAmount,
                    'payment_date' => now(),
                    'gcash_proof_path' => $proofPath,
                ]);

                return $order;
            });
        } catch (Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('orders.completed', $order)
            ->with('success', 'Order confirmed and payment recorded successfully.');
    }

    public function completed(Order $order)
    {
        $this->authorizeOrder($order);

        abort_unless(
            $order->payments()->exists(),
            403,
            'Only paid orders can show a completion receipt.'
        );

        $order->load([
            'employee',
            'orderItems.product',
            'payments',
        ]);

        $chickenBatches = $order->is_fresh_chicken
            ? CookingBatch::query()
                ->where('food_type', 'chicken')
                ->where('status', 'cooking')
                ->orderByDesc('started_at')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view(
            'orders.completed',
            compact('order', 'chickenBatches')
        );
    }

    public function show(Order $order)
    {
        $this->authorizeOrder($order);

        $order->load([
            'employee',
            'orderItems.product.productIngredients.ingredient',
            'payments',
        ]);

        $chickenBatches = $order->is_fresh_chicken
            ? CookingBatch::query()
                ->where('food_type', 'chicken')
                ->where('status', 'cooking')
                ->orderByDesc('started_at')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view(
            'orders.show',
            compact('order', 'chickenBatches')
        );
    }

    public function edit(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status === 'Completed') {
            return back()
                ->withErrors([
                    'order' => 'Completed orders can no longer be edited.',
                ]);
        }

        $products = Product::where(
            'status',
            'Active'
        )
            ->with(
                'productIngredients.ingredient'
            )
            ->orderBy('product_name')
            ->get();

        $order->load([
            'orderItems.product',
            'payments',
        ]);

        return view(
            'orders.edit',
            compact(
                'order',
                'products'
            )
        );
    }

    public function update(
        Request $request,
        Order $order
    ) {
        $this->authorizeOrder($order);

        if ($order->status === 'Completed') {
            return back()
                ->withErrors([
                    'order' => 'Completed orders can no longer be edited.',
                ]);
        }

        $validated = $request->validate([
            'order_type' => [
                'required',
                'in:Dine-in,Take-out',
            ],

            'is_fresh_chicken' => [
                'nullable',
                'in:0,1',
            ],

            'items' => [
                'required',
                'array',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'additional_amount_paid' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'additional_payment_method' => [
                'nullable',
                'in:Cash,GCash',
            ],
        ]);

        $selectedItems = [];

        foreach (
            $validated['items'] as $productId => $item
        ) {
            $quantity = (int) $item['quantity'];

            if ($quantity > 0) {
                $selectedItems[] = [
                    'product_id' => (int) $productId,
                    'quantity' => $quantity,
                ];
            }
        }

        if (count($selectedItems) === 0) {
            return back()
                ->withErrors([
                    'items' => 'Please select at least one product.',
                ])
                ->withInput();
        }

        $isFreshChicken =
            $validated['is_fresh_chicken'] === '1'
            || $validated['is_fresh_chicken'] === 1;

        $previousTotal = (float) $order->total_amount;
        $additionalAmountPaid = (float) ($validated['additional_amount_paid'] ?? 0);
        $additionalPaymentMethod = $validated['additional_payment_method'] ?? null;

        $selectedProducts = Product::query()
            ->whereIn('product_id', array_column($selectedItems, 'product_id'))
            ->where('status', 'Active')
            ->get()
            ->keyBy('product_id');

        $newTotal = 0.0;

        foreach ($selectedItems as $item) {
            $product = $selectedProducts->get($item['product_id']);

            if (! $product) {
                return back()
                    ->withErrors([
                        'order' => 'One of the products in this order no longer exists.',
                    ])
                    ->withInput();
            }

            $newTotal += (float) $product->price * (int) $item['quantity'];
        }

        /*
         * Validate the additional payment before touching the database.
         * Failing inside the transaction after the order items were already
         * replaced would commit a half-applied order.
         */
        $balanceDue = $newTotal - $previousTotal;

        if ($balanceDue > 0) {
            if ($additionalAmountPaid <= 0 || $additionalPaymentMethod === null) {
                return back()
                    ->withErrors([
                        'order' => 'The order total increased by ₱'.number_format($balanceDue, 2)
                            .'. Record the additional payment before saving.',
                    ])
                    ->withInput();
            }

            if (round($additionalAmountPaid * 100) < round($balanceDue * 100)) {
                return back()
                    ->withErrors([
                        'order' => 'The additional payment of ₱'.number_format($additionalAmountPaid, 2)
                            .' is less than the ₱'.number_format($balanceDue, 2).' still owed.',
                    ])
                    ->withInput();
            }
        }

        $updateError = DB::transaction(function () use (
            $selectedItems,
            $validated,
            $order,
            $isFreshChicken,
            $previousTotal,
            $additionalAmountPaid,
            $additionalPaymentMethod
        ) {
            $reconcileError = $this->reconcileOrderIngredients($order, $selectedItems);

            if ($reconcileError !== null) {
                return $reconcileError;
            }

            $order->orderItems()->delete();

            $totalAmount = 0;

            foreach (
                $selectedItems as $item
            ) {
                $product = Product::where(
                    'product_id',
                    $item['product_id']
                )
                    ->where(
                        'status',
                        'Active'
                    )
                    ->firstOrFail();

                $quantity =
                    (int) $item['quantity'];

                $unitPrice =
                    (float) $product->price;

                $subtotal =
                    $unitPrice * $quantity;

                OrderItem::create([
                    'order_id' => $order->order_id,

                    'product_id' => $product->product_id,

                    'quantity' => $quantity,

                    'unit_price' => $unitPrice,

                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $balanceDue = $totalAmount - $previousTotal;

            $order->update([
                'order_type' => $validated['order_type'],

                'is_fresh_chicken' => $isFreshChicken,

                'total_amount' => $totalAmount,
            ]);

            if ($balanceDue > 0) {
                Payment::create([
                    'order_id' => $order->order_id,
                    'payment_method' => $additionalPaymentMethod,
                    'amount_paid' => $additionalAmountPaid,
                    'change_amount' => max(0, $additionalAmountPaid - $balanceDue),
                    'payment_date' => now(),
                ]);
            }

            return null;
        });

        if ($updateError !== null) {
            return back()
                ->withErrors([
                    'order' => $updateError,
                ])
                ->withInput();
        }

        return redirect()
            ->route(
                'orders.show',
                $order
            )
            ->with(
                'success',
                'Order updated successfully.'
            );
    }

    public function destroy(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'Pending') {
            return back()
                ->withErrors([
                    'order' => 'Only pending orders can be deleted.',
                ]);
        }

        if ($order->payments()->exists()) {
            return back()
                ->withErrors([
                    'order' => 'This order cannot be deleted because it already has a payment.',
                ]);
        }

        $order->delete();

        return redirect()
            ->route('orders.index')
            ->with(
                'success',
                'Order deleted successfully.'
            );
    }

    public function confirm(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'Pending') {
            return back()
                ->withErrors([
                    'order' => 'Only pending orders can be confirmed.',
                ]);
        }

        if ($order->payments()->exists()) {
            return back()
                ->withErrors([
                    'order' => 'This order has already been paid and cannot be confirmed again.',
                ]);
        }

        $confirmationError = DB::transaction(function () use ($order): ?string {
            $confirmationError = $this->deductOrderIngredients($order);

            if ($confirmationError === null) {
                $order->update([
                    'status' => 'Confirmed',
                    'ready_at' => null,
                ]);
            }

            return $confirmationError;
        });

        if ($confirmationError) {
            return back()
                ->withErrors([
                    'order' => $confirmationError,
                ]);
        }

        return redirect()
            ->route(
                'orders.show',
                $order
            )
            ->with(
                'success',
                'Order confirmed and ingredients deducted from inventory.'
            );
    }

    public function preparing(Order $order)
    {
        /*
         * Kitchen Staff must be able
         * to handle any order.
         */
        $this->authorizeKitchenAction();

        if ($order->status !== 'Confirmed') {
            return back()
                ->withErrors([
                    'order' => 'Only confirmed orders can be marked as preparing.',
                ]);
        }

        $order->update([
            'status' => 'Preparing',
        ]);

        return back()
            ->with(
                'success',
                'Order marked as preparing.'
            );
    }

    public function ready(Order $order)
    {
        /*
         * Kitchen Staff must be able
         * to handle any order.
         */
        $this->authorizeKitchenAction();

        if ($order->status !== 'Preparing') {
            return back()
                ->withErrors([
                    'order' => 'Only preparing orders can be marked as ready.',
                ]);
        }

        $order->update([
            'status' => 'Ready',

            'ready_at' => now(),
        ]);

        return back()
            ->with(
                'success',
                'Order marked as ready.'
            );
    }

    public function startCookingBatch(Request $request): RedirectResponse
    {
        $this->authorizeKitchenAction();

        $validated = $request->validate([
            'food_type' => ['required', 'in:chicken'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        DB::transaction(function () use ($validated): void {
            $batch = CookingBatch::create([
                'employee_id' => auth()->user()->employee_id,
                'food_type' => $validated['food_type'],
                'quantity' => $validated['quantity'],
                'duration_minutes' => $validated['duration_minutes'],
                'started_at' => now(),
            ]);

            if ($batch->food_type === 'chicken') {
                $this->notifyCashiersAboutFreshChickenBatch($batch, 'cooking');
            }
        });

        return redirect()
            ->route('kitchen.index')
            ->with('success', ucfirst($validated['food_type']).' batch timer started.');
    }

    public function markCookingBatchReady(CookingBatch $batch): RedirectResponse
    {
        $this->authorizeKitchenAction();

        DB::transaction(function () use ($batch): void {
            $batch = CookingBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->getKey());

            if ($batch->status !== 'cooking') {
                throw ValidationException::withMessages([
                    'batch' => 'This cooking batch has already been marked ready.',
                ]);
            }

            $readyAt = $batch->started_at->copy()
                ->addMinutes($batch->duration_minutes);

            if (now()->lt($readyAt)) {
                throw ValidationException::withMessages([
                    'batch' => 'The batch timer must finish before the batch can be marked ready.',
                ]);
            }

            $batch->update([
                'status' => 'ready',
                'ready_at' => now(),
            ]);

            if ($batch->food_type === 'chicken') {
                Order::query()
                    ->where('is_fresh_chicken', true)
                    ->where('status', 'Pending')
                    ->whereHas('payments')
                    ->update([
                        'status' => 'Ready',
                        'ready_at' => now(),
                    ]);

                $this->notifyCashiersAboutFreshChickenBatch($batch, 'ready');
            }
        });

        return redirect()
            ->route('kitchen.index')
            ->with('success', 'Chicken batch confirmed ready.');
    }

    public function complete(Order $order)
    {
        $this->authorizeOrder($order);

        if (! in_array($order->status, ['Paid', 'Ready'], true) || ! $order->payments()->exists()) {
            return back()
                ->withErrors([
                    'order' => 'Only paid orders can be completed.',
                ]);
        }

        $order->update([
            'status' => 'Completed',
        ]);

        return redirect()
            ->route('orders.index', ['status_order' => $order->order_id])
            ->with(
                'success',
                'Order completed successfully.'
            );
    }

    private function deductOrderIngredients(Order $order): ?string
    {
        $order->load([
            'orderItems.product.productIngredients.ingredient',
        ]);

        $requiredIngredients = $this->collectRequiredIngredients($order);

        if ($requiredIngredients instanceof string) {
            return $requiredIngredients;
        }

        return $this->applyIngredientChanges($requiredIngredients);
    }

    /**
     * Reconcile inventory against an order after its items changed.
     *
     * Returns stock for ingredients no longer needed and deducts stock for
     * newly added ingredients, so editing an order can never let inventory
     * drift away from the recipes that were actually consumed.
     */
    private function reconcileOrderIngredients(Order $order, array $newItems): ?string
    {
        $previousRequirements = $this->collectRequiredIngredients($order);

        if ($previousRequirements instanceof string) {
            return $previousRequirements;
        }

        $newRequirements = $this->collectRequirementsForItems($newItems);

        if ($newRequirements instanceof string) {
            return $newRequirements;
        }

        $ingredientChanges = [];

        foreach ($previousRequirements as $ingredientId => $requirement) {
            $ingredientChanges[$ingredientId] = [
                'ingredient_name' => $requirement['ingredient_name'],
                'quantity' => -$requirement['quantity'],
            ];
        }

        foreach ($newRequirements as $ingredientId => $requirement) {
            $ingredientChanges[$ingredientId] ??= [
                'ingredient_name' => $requirement['ingredient_name'],
                'quantity' => 0,
            ];
            $ingredientChanges[$ingredientId]['quantity'] += $requirement['quantity'];
        }

        return $this->applyIngredientChanges($ingredientChanges);
    }

    /**
     * Sum the ingredients an order's current items consume.
     *
     * @return array<int, array{ingredient_name: string, quantity: float}>|string
     */
    private function collectRequiredIngredients(Order $order): array|string
    {
        $items = $order->orderItems
            ->map(fn (OrderItem $orderItem): array => [
                'product_id' => (int) $orderItem->product_id,
                'quantity' => (int) $orderItem->quantity,
            ])
            ->all();

        return $this->collectRequirementsForItems($items);
    }

    /**
     * Sum the ingredients a set of product quantities consumes.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @return array<int, array{ingredient_name: string, quantity: float}>|string
     */
    private function collectRequirementsForItems(array $items): array|string
    {
        if ($items === []) {
            return 'This order has no items.';
        }

        $products = Product::query()
            ->with('productIngredients.ingredient')
            ->whereIn('product_id', array_column($items, 'product_id'))
            ->get()
            ->keyBy('product_id');

        $requiredIngredients = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                return 'One of the products in this order no longer exists.';
            }

            if ($product->productIngredients->isEmpty()) {
                return "The product \"{$product->product_name}\" has no recipe ingredients.";
            }

            foreach ($product->productIngredients as $recipeItem) {
                $requiredQuantity = (float) $recipeItem->quantity_required * (int) $item['quantity'];
                $ingredientId = (int) $recipeItem->ingredient_id;
                $requiredIngredients[$ingredientId] ??= [
                    'ingredient_name' => $recipeItem->ingredient?->ingredient_name ?? 'Unknown ingredient',
                    'quantity' => 0,
                ];
                $requiredIngredients[$ingredientId]['quantity'] += $requiredQuantity;
            }
        }

        return $requiredIngredients;
    }

    /**
     * Apply signed ingredient changes to inventory, locking every row before
     * it is read so two concurrent edits cannot both pass the stock check.
     *
     * @param  array<int, array{ingredient_name: string, quantity: float}>  $changes
     */
    private function applyIngredientChanges(array $changes): ?string
    {
        $inventories = [];

        foreach ($changes as $ingredientId => $change) {
            $inventory = Inventory::query()
                ->where('ingredient_id', $ingredientId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                return "No inventory record exists for {$change['ingredient_name']}.";
            }

            if ($change['quantity'] > 0
                && (float) $inventory->quantity < $change['quantity']
            ) {
                return "Insufficient {$change['ingredient_name']}. Required: {$change['quantity']}, Available: {$inventory->quantity}.";
            }

            $inventories[$ingredientId] = $inventory;
        }

        foreach ($changes as $ingredientId => $change) {
            if ($change['quantity'] == 0) {
                continue;
            }

            $inventories[$ingredientId]->update([
                'quantity' => (float) $inventories[$ingredientId]->quantity - $change['quantity'],
                'last_updated' => now(),
            ]);
        }

        return null;
    }

    /*
     * Normal order access.
     *
     * Manager:
     * Can access all orders.
     *
     * Cashier:
     * Can access orders created
     * by their own employee account.
     */
    private function authorizeOrder(
        Order $order
    ): void {
        $user = auth()->user();

        $role =
            $user->employee?->role?->role_name;

        if ($role === 'Manager') {
            return;
        }

        if (
            $role === 'Cashier' &&
            $order->employee_id ===
            $user->employee_id
        ) {
            return;
        }

        abort(
            403,
            'You are not authorized to access this order.'
        );
    }

    /*
     * Kitchen action access.
     *
     * Manager:
     * Can prepare and ready any order.
     *
     * Kitchen Staff:
     * Can prepare and ready any order.
     */
    private function authorizeKitchenAction(): void
    {
        $user = auth()->user();

        $role =
            $user->employee?->role?->role_name;

        if (
            in_array(
                $role,
                [
                    'Manager',
                    'Kitchen Staff',
                ]
            )
        ) {
            return;
        }

        abort(
            403,
            'Only the Manager or Kitchen Staff can manage the preparation status of orders.'
        );
    }

    private function notifyCashiersAboutFreshChickenBatch(
        CookingBatch $batch,
        string $status
    ): void {
        $waitingOrderIds = Order::query()
            ->where('is_fresh_chicken', true)
            ->whereIn('status', ['Pending', 'Confirmed', 'Preparing', 'Ready'])
            ->orderBy('order_id')
            ->pluck('order_id')
            ->all();

        if ($waitingOrderIds === []) {
            return;
        }

        $cashiers = User::query()
            ->whereHas('employee', function ($employeeQuery): void {
                $employeeQuery
                    ->where('status', 'Active')
                    ->whereHas('role', function ($roleQuery): void {
                        $roleQuery->where('role_name', 'Cashier');
                    });
            })
            ->get();

        if ($cashiers->isEmpty()) {
            throw ValidationException::withMessages([
                'batch' => 'No active cashier account is available to notify.',
            ]);
        }

        Notification::send(
            $cashiers,
            new CookingBatchNotification(
                batchId: $batch->getKey(),
                status: $status,
                quantity: $batch->quantity,
                waitingOrderCount: count($waitingOrderIds),
                waitingOrderIds: $waitingOrderIds,
                startedAt: $batch->started_at->toIso8601String(),
                durationMinutes: $batch->duration_minutes,
            )
        );
    }

    public function kitchen()
    {
        $batches = CookingBatch::with('employee')
            ->where('food_type', 'chicken')
            ->whereDate('started_at', today())
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();

        return view(
            'kitchen.index',
            compact('batches')
        );
    }

    public function resetCookingBatches(): RedirectResponse
    {
        $this->authorizeKitchenAction();

        CookingBatch::query()
            ->whereDate('started_at', today())
            ->delete();

        return redirect()
            ->route('kitchen.index')
            ->with('success', "Today's cooking queue has been reset.");
    }
}
