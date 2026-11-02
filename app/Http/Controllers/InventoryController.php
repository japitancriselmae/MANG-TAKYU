<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\InventoryCategory;
use App\Models\ProductIngredient;
use App\Models\StockMovement;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->query('tab') === 'recipes'
            ? 'recipes'
            : ($request->query('tab') === 'ingredients' ? 'ingredients' : 'stock');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'stock_level' => ['nullable', 'in:in-stock,low-stock,out-of-stock'],
            'sort' => ['nullable', 'in:name,quantity,updated'],
        ]);

        $inventories = Inventory::query()
            ->with(['ingredient.inventoryCategory'])
            ->whereHas('ingredient', function (Builder $query) use ($filters): void {
                if (! empty($filters['search'])) {
                    $search = '%'.$filters['search'].'%';
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('ingredient_name', 'like', $search)
                            ->orWhere('sku', 'like', $search);
                    });
                }

                if (! empty($filters['category'])) {
                    $query->where('inventory_category_id', $filters['category']);
                }
            })
            ->when(
                ($filters['stock_level'] ?? null) === 'in-stock',
                fn (Builder $query) => $query->whereColumn('quantity', '>', 'minimum_stock')
            )
            ->when(
                ($filters['stock_level'] ?? null) === 'low-stock',
                fn (Builder $query) => $query->where('quantity', '>', 0)
                    ->whereColumn('quantity', '<=', 'minimum_stock')
            )
            ->when(
                ($filters['stock_level'] ?? null) === 'out-of-stock',
                fn (Builder $query) => $query->where('quantity', '<=', 0)
            );

        $inventories = match ($filters['sort'] ?? 'name') {
            'quantity' => $inventories->orderBy('quantity')->orderBy('inventory_id'),
            'updated' => $inventories->orderByDesc('last_updated')->orderByDesc('inventory_id'),
            default => $inventories->orderBy(
                Ingredient::query()
                    ->select('ingredient_name')
                    ->whereColumn('ingredients.ingredient_id', 'inventories.ingredient_id')
            )->orderBy('inventory_id'),
        };
        $inventories = $inventories->paginate(15)->withQueryString();

        $productIngredients = ProductIngredient::with([
            'product',
            'ingredient',
        ])
            ->orderBy('product_id')
            ->orderBy('ingredient_id')
            ->get();

        $ingredients = Ingredient::orderBy('ingredient_name')->get();
        $categories = InventoryCategory::orderBy('name')->get();

        return view(
            'inventories.index',
            compact(
                'inventories',
                'productIngredients',
                'ingredients',
                'categories',
                'filters',
                'activeTab'
            )
        );
    }

    public function create()
    {
        $ingredients = Ingredient::orderBy('ingredient_name')->get();

        return view(
            'inventories.create',
            compact('ingredients')
        );
    }

    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
                'unique:inventories,ingredient_id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],

            'minimum_stock' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],

            'last_updated' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
        ]);

        DB::transaction(function () use ($validated, $activityLogger): void {
            $inventory = Inventory::create($validated);

            $activityLogger->record(
                action: 'inventory.created',
                module: 'inventory',
                description: 'Inventory record created.',
                subject: $inventory,
                newValues: $this->auditValues($inventory),
            );

            if ((float) $inventory->quantity > 0) {
                $stockMovement = StockMovement::create([
                    'ingredient_id' => $inventory->ingredient_id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'Opening Balance',
                    'quantity' => $inventory->quantity,
                    'movement_date' => $inventory->last_updated,
                    'reference' => 'Initial stock',
                    'remarks' => 'Opening balance',
                ]);

                $this->recordStockMovement($activityLogger, $stockMovement);
            }
        });

        return redirect()
            ->route('inventories.index')
            ->with(
                'success',
                'Inventory created successfully.'
            );
    }

    public function show(Inventory $inventory)
    {
        $inventory->load('ingredient');

        return view(
            'inventories.show',
            compact('inventory')
        );
    }

    public function edit(Inventory $inventory)
    {
        $ingredients = Ingredient::orderBy('ingredient_name')->get();

        $inventory->load('ingredient');

        return view(
            'inventories.edit',
            compact(
                'inventory',
                'ingredients'
            )
        );
    }

    public function update(
        Request $request,
        Inventory $inventory,
        ActivityLogger $activityLogger,
    ) {
        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                Rule::in([$inventory->ingredient_id]),
            ],

            'quantity' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],

            'minimum_stock' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],

            'last_updated' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
        ]);

        DB::transaction(function () use ($validated, $inventory, $activityLogger): void {
            $inventory = Inventory::query()
                ->whereKey($inventory->inventory_id)
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = $this->auditValues($inventory);
            $quantityDelta = round(
                (float) $validated['quantity'] - (float) $inventory->quantity,
                2
            );

            $inventory->update(array_merge($validated, [
                'last_updated' => now(),
            ]));

            if ($quantityDelta !== 0.0) {
                $stockMovement = StockMovement::create([
                    'ingredient_id' => $inventory->ingredient_id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'Adjustment',
                    'quantity' => $quantityDelta,
                    'movement_date' => now(),
                    'reference' => 'Manual inventory edit',
                    'remarks' => 'Manual inventory adjustment',
                ]);

                $this->recordStockMovement($activityLogger, $stockMovement);
            }

            $activityLogger->record(
                action: 'inventory.updated',
                module: 'inventory',
                description: 'Inventory quantity or threshold updated.',
                subject: $inventory,
                oldValues: $oldValues,
                newValues: $this->auditValues($inventory),
            );
        });

        return redirect()
            ->route('inventories.index')
            ->with(
                'success',
                'Inventory updated successfully.'
            );
    }

    public function destroy(Inventory $inventory, ActivityLogger $activityLogger)
    {
        if (StockMovement::where('ingredient_id', $inventory->ingredient_id)->exists()) {
            return back()->withErrors([
                'inventory' => 'This stock item cannot be deleted because it has movement history.',
            ]);
        }

        DB::transaction(function () use ($inventory, $activityLogger): void {
            $oldValues = $this->auditValues($inventory);
            $inventory->delete();

            $activityLogger->record(
                action: 'inventory.deleted',
                module: 'inventory',
                description: 'Inventory record deleted.',
                subject: $inventory,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('inventories.index')
            ->with(
                'success',
                'Inventory deleted successfully.'
            );
    }

    /**
     * @return array{ ingredient_id: int, quantity: string, minimum_stock: string }
     */
    private function auditValues(Inventory $inventory): array
    {
        return [
            'ingredient_id' => $inventory->ingredient_id,
            'quantity' => (string) $inventory->quantity,
            'minimum_stock' => (string) $inventory->minimum_stock,
        ];
    }

    private function recordStockMovement(ActivityLogger $activityLogger, StockMovement $stockMovement): void
    {
        $activityLogger->record(
            action: 'stock.movement_recorded',
            module: 'inventory',
            description: 'Stock movement recorded.',
            subject: $stockMovement,
            newValues: [
                'ingredient_id' => $stockMovement->ingredient_id,
                'movement_type' => $stockMovement->movement_type,
                'quantity' => (string) $stockMovement->quantity,
            ],
        );
    }
}
