<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->employee?->role?->role_name;

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

        $orders = $query->get();

        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $products = Product::where('status', 'Active')
            ->with('productIngredients.ingredient')
            ->orderBy('product_name')
            ->get();

        return view('orders.create', compact('products'));
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
        ]);

        $employeeId = auth()->user()->employee_id;

        if (!$employeeId) {
            return back()
                ->withErrors([
                    'order' =>
                    'Your account is not linked to an employee.',
                ])
                ->withInput();
        }

        $isFreshChicken =
            $request->boolean('is_fresh_chicken');

        $order = DB::transaction(function () use (
            $validated,
            $employeeId,
            $isFreshChicken
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

                $product = Product::where(
                    'product_id',
                    $item['product_id']
                )
                    ->where('status', 'Active')
                    ->firstOrFail();

                $quantity = (int) $item['quantity'];

                $unitPrice = (float) $product->price;

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

            $order->update([
                'total_amount' => $totalAmount,
            ]);

            return $order;
        });

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'success',
                'Order created successfully.'
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

        return view(
            'orders.show',
            compact('order')
        );
    }

    public function edit(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'Pending') {
            return back()
                ->withErrors([
                    'order' =>
                    'Only pending orders can be edited.',
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

        $order->load(
            'orderItems.product'
        );

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

        if ($order->status !== 'Pending') {
            return back()
                ->withErrors([
                    'order' =>
                    'Only pending orders can be edited.',
                ]);
        }

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
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $selectedItems = [];

        foreach (
            $validated['items']
            as $productId => $item
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
                    'items' =>
                    'Please select at least one product.',
                ])
                ->withInput();
        }

        $isFreshChicken =
            $request->boolean(
                'is_fresh_chicken'
            );

        DB::transaction(function () use (
            $selectedItems,
            $validated,
            $order,
            $isFreshChicken
        ) {
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
                    'order_id' =>
                    $order->order_id,

                    'product_id' =>
                    $product->product_id,

                    'quantity' =>
                    $quantity,

                    'unit_price' =>
                    $unitPrice,

                    'subtotal' =>
                    $subtotal,
                ]);

                $totalAmount += $subtotal;
            }

            $order->update([
                'order_type' =>
                $validated['order_type'],

                'is_fresh_chicken' =>
                $isFreshChicken,

                'total_amount' =>
                $totalAmount,
            ]);
        });

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
                    'order' =>
                    'Only pending orders can be deleted.',
                ]);
        }

        if ($order->payments()->exists()) {
            return back()
                ->withErrors([
                    'order' =>
                    'This order cannot be deleted because it already has a payment.',
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
                    'order' =>
                    'Only pending orders can be confirmed.',
                ]);
        }

        $confirmationError = null;

        DB::transaction(function () use (
            $order,
            &$confirmationError
        ) {
            $order->load([
                'orderItems.product.productIngredients.ingredient',
            ]);

            if ($order->orderItems->isEmpty()) {
                $confirmationError =
                    'This order has no items.';

                return;
            }

            /*
             * FIRST:
             * Check all required ingredients.
             */
            foreach (
                $order->orderItems as $orderItem
            ) {
                $product =
                    $orderItem->product;

                if (!$product) {
                    $confirmationError =
                        'One of the products in this order no longer exists.';

                    return;
                }

                $recipe =
                    $product->productIngredients;

                if ($recipe->isEmpty()) {
                    $confirmationError =
                        "The product \"{$product->product_name}\" has no recipe ingredients.";

                    return;
                }

                foreach (
                    $recipe as $recipeItem
                ) {
                    $requiredQuantity =
                        (float)
                        $recipeItem->quantity_required
                        *
                        (int)
                        $orderItem->quantity;

                    $inventory =
                        Inventory::where(
                            'ingredient_id',
                            $recipeItem->ingredient_id
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$inventory) {
                        $ingredientName =
                            $recipeItem->ingredient?->ingredient_name
                            ??
                            'Unknown ingredient';

                        $confirmationError =
                            "No inventory record exists for {$ingredientName}.";

                        return;
                    }

                    if (
                        (float)
                        $inventory->quantity
                        <
                        $requiredQuantity
                    ) {
                        $ingredientName =
                            $recipeItem->ingredient?->ingredient_name
                            ??
                            'Unknown ingredient';

                        $confirmationError =
                            "Insufficient {$ingredientName}. "
                            .
                            "Required: {$requiredQuantity}, "
                            .
                            "Available: {$inventory->quantity}.";

                        return;
                    }
                }
            }


            /*
             * SECOND:
             * Deduct ingredients.
             */
            foreach (
                $order->orderItems as $orderItem
            ) {
                foreach (
                    $orderItem
                        ->product
                        ->productIngredients
                    as $recipeItem
                ) {
                    $requiredQuantity =
                        (float)
                        $recipeItem->quantity_required
                        *
                        (int)
                        $orderItem->quantity;

                    $inventory =
                        Inventory::where(
                            'ingredient_id',
                            $recipeItem->ingredient_id
                        )
                        ->lockForUpdate()
                        ->first();

                    $inventory->update([
                        'quantity' =>
                        (float)
                        $inventory->quantity
                            -
                            $requiredQuantity,

                        'last_updated' =>
                        now(),
                    ]);
                }
            }


            /*
             * FRESH CHICKEN:
             * Start the 12-minute timer
             * when the order is confirmed.
             */
            $estimatedReadyAt = null;

            if ($order->is_fresh_chicken) {
                $estimatedReadyAt =
                    now()->addMinutes(12);
            }


            $order->update([
                'status' =>
                'Confirmed',

                'estimated_ready_at' =>
                $estimatedReadyAt,

                'ready_at' =>
                null,
            ]);
        });


        if ($confirmationError) {
            return back()
                ->withErrors([
                    'order' =>
                    $confirmationError,
                ]);
        }


        $message =
            $order->is_fresh_chicken
            ? 'Fresh chicken order confirmed. The 12-minute preparation timer has started.'
            : 'Order confirmed and ingredients deducted from inventory.';


        return redirect()
            ->route(
                'orders.show',
                $order
            )
            ->with(
                'success',
                $message
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
                    'order' =>
                    'Only confirmed orders can be marked as preparing.',
                ]);
        }

        $order->update([
            'status' =>
            'Preparing',
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
                    'order' =>
                    'Only preparing orders can be marked as ready.',
                ]);
        }

        $order->update([
            'status' =>
            'Ready',

            'ready_at' =>
            now(),
        ]);

        return back()
            ->with(
                'success',
                'Order marked as ready.'
            );
    }

    public function complete(Order $order)
    {
        $this->authorizeOrder($order);

        if ($order->status !== 'Paid') {
            return back()
                ->withErrors([
                    'order' =>
                    'Only paid orders can be completed.',
                ]);
        }

        $order->update([
            'status' =>
            'Completed',
        ]);

        return back()
            ->with(
                'success',
                'Order completed successfully.'
            );
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
    public function kitchen()
    {
        $orders = Order::with([
            'employee',
            'orderItems.product',
        ])
            ->whereIn('status', [
                'Confirmed',
                'Preparing',
            ])
            ->orderBy('estimated_ready_at')
            ->orderBy('order_id')
            ->get();

        return view(
            'kitchen.index',
            compact('orders')
        );
    }
}
