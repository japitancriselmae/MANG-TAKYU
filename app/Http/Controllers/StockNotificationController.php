<?php

namespace App\Http\Controllers;

use App\Models\Inventory;

class StockNotificationController extends Controller
{
    public function index()
    {
        $inventories = Inventory::with('ingredient')
            ->orderBy('quantity')
            ->get();

        $outOfStockInventories = $inventories
            ->filter(function ($inventory) {
                return (float) $inventory->quantity <= 0;
            })
            ->values();

        $lowStockInventories = $inventories
            ->filter(function ($inventory) {
                return (float) $inventory->quantity > 0
                    && (float) $inventory->quantity
                    <= (float) $inventory->minimum_stock;
            })
            ->values();

        $normalStockInventories = $inventories
            ->filter(function ($inventory) {
                return (float) $inventory->quantity
                    > (float) $inventory->minimum_stock;
            })
            ->values();

        return view(
            'stock-notifications.index',
            compact(
                'inventories',
                'outOfStockInventories',
                'lowStockInventories',
                'normalStockInventories'
            )
        );
    }
}
