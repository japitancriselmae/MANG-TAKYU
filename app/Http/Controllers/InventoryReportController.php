<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:inventory,stock-in,stock-out,low-stock,out-of-stock,valuation'],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end_date' => array_filter([
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
                $request->filled('start_date') ? 'after_or_equal:start_date' : null,
            ]),
        ]);

        $type = $filters['type'] ?? 'inventory';
        $isMovementReport = in_array($type, ['stock-in', 'stock-out'], true);
        $inventories = collect();
        $movements = collect();
        $valuation = 0.0;

        if ($isMovementReport) {
            $movementType = $type === 'stock-in' ? 'Stock In' : 'Stock Out';
            $query = StockMovement::query()
                ->with(['ingredient.inventoryCategory', 'user'])
                ->whereIn(
                    'movement_type',
                    $type === 'stock-in'
                        ? ['Stock In', 'Opening Balance']
                        : [$movementType]
                )
                ->orderByDesc('movement_date')
                ->orderByDesc('movement_id');

            if (isset($filters['start_date'])) {
                $query->whereDate('movement_date', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date'])) {
                $query->whereDate('movement_date', '<=', $filters['end_date']);
            }

            $movements = $query->paginate(25)->withQueryString();
        } else {
            $query = Inventory::query()
                ->with(['ingredient.inventoryCategory'])
                ->join('ingredients', 'inventories.ingredient_id', '=', 'ingredients.ingredient_id')
                ->select('inventories.*');

            if ($type === 'low-stock') {
                $query->where('inventories.quantity', '>', 0)
                    ->whereColumn('inventories.quantity', '<=', 'inventories.minimum_stock');
            } elseif ($type === 'out-of-stock') {
                $query->where('inventories.quantity', '<=', 0);
            }

            if ($type === 'valuation') {
                $valuation = (float) Inventory::query()
                    ->join('ingredients', 'inventories.ingredient_id', '=', 'ingredients.ingredient_id')
                    ->selectRaw('COALESCE(SUM(inventories.quantity * ingredients.purchase_price), 0) as valuation')
                    ->value('valuation');
            }

            $inventories = $query
                ->orderBy('ingredients.ingredient_name')
                ->paginate(25)
                ->withQueryString();
        }

        return view('reports.inventory', compact(
            'type',
            'filters',
            'isMovementReport',
            'inventories',
            'movements',
            'valuation',
        ));
    }
}
