<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:Stock In,Stock Out,Adjustment,Opening Balance'],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end_date' => array_filter([
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
                $request->filled('start_date') ? 'after_or_equal:start_date' : null,
            ]),
        ]);

        $stockMovements = StockMovement::query()
            ->with(['ingredient.inventoryCategory', 'user'])
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $query->whereHas(
                    'ingredient',
                    fn (Builder $query) => $query
                        ->where('ingredient_name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                )
            )
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('movement_type', $type))
            ->when($filters['start_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('movement_date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('movement_date', '<=', $date))
            ->orderByDesc('movement_date')
            ->orderByDesc('movement_id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'stock-movements.index',
            compact('stockMovements', 'filters')
        );
    }

    public function create(): View
    {
        $ingredients = Ingredient::query()
            ->whereHas('inventories')
            ->with('inventories')
            ->orderBy('ingredient_name')
            ->get();

        return view(
            'stock-movements.create',
            compact('ingredients')
        );
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $isAdjustment = $request->input('movement_type') === 'Adjustment';

        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:inventories,ingredient_id',
            ],

            'movement_type' => [
                'required',
                'in:Stock In,Stock Out,Adjustment',
            ],

            'quantity' => [
                'required',
                'numeric',
                'decimal:0,2',
                $isAdjustment ? 'min:0' : 'min:0.01',
            ],

            'movement_date' => [
                'required',
                'date',
                'before_or_equal:now',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use ($validated, $activityLogger): void {
            $inventory = Inventory::query()
                ->where('ingredient_id', $validated['ingredient_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $quantity = (float) $validated['quantity'];
            $delta = match ($validated['movement_type']) {
                'Stock In' => $quantity,
                'Stock Out' => -$quantity,
                'Adjustment' => round($quantity - (float) $inventory->quantity, 2),
            };

            if ($delta < 0 && (float) $inventory->quantity + $delta < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'The stock out exceeds the available quantity.',
                ]);
            }

            $movementDate = $validated['movement_date'];
            $inventory->update([
                'quantity' => round((float) $inventory->quantity + $delta, 2),
                'last_updated' => now(),
            ]);

            $stockMovement = StockMovement::create([
                'ingredient_id' => $inventory->ingredient_id,
                'user_id' => auth()->id(),
                'movement_type' => $validated['movement_type'],
                'quantity' => $delta,
                'movement_date' => $movementDate,
                'reference' => $validated['reference'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

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
        });

        return redirect()
            ->route('stock-movements.index')
            ->with(
                'success',
                'Stock movement created successfully.'
            );
    }

    public function show(StockMovement $stockMovement): View
    {
        $stockMovement->load(['ingredient', 'user']);

        return view(
            'stock-movements.show',
            compact('stockMovement')
        );
    }
}
