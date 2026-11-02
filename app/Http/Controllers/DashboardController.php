<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryCategory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $canViewSalesComparison = true;
        $today = Carbon::today();

        $startOfDay = Carbon::today();
        $startOfTomorrow = $startOfDay->copy()->addDay();

        $ordersToday = Order::query()
            ->where('order_date', '>=', $startOfDay)
            ->where('order_date', '<', $startOfTomorrow)
            ->selectRaw(
                'COUNT(*) as total_orders, '.
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_orders',
                ['Pending'],
            )
            ->firstOrFail();

        $paymentsToday = Payment::query()
            ->where('payment_date', '>=', $startOfDay)
            ->where('payment_date', '<', $startOfTomorrow)
            ->selectRaw(
                'COALESCE(SUM(amount_paid - change_amount), 0) as sales_today',
            )
            ->firstOrFail();

        $viewData = [
            'totalOrders' => (int) $ordersToday->total_orders,
            'pendingOrders' => (int) $ordersToday->pending_orders,
            'salesToday' => (float) $paymentsToday->sales_today,
            'canViewSalesComparison' => $canViewSalesComparison,
        ];

        $roleName = $request->user()?->employee?->role?->role_name;

        if (in_array($roleName, ['Admin', 'Manager'], true)) {
            $inventoryOverview = Inventory::query()
                ->selectRaw('COUNT(*) as tracked_items')
                ->selectRaw('COALESCE(SUM(quantity), 0) as total_stock')
                ->selectRaw('SUM(CASE WHEN quantity > 0 AND quantity <= minimum_stock THEN 1 ELSE 0 END) as low_stock')
                ->selectRaw('SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock')
                ->firstOrFail();

            $inventoryValue = Inventory::query()
                ->join('ingredients', 'inventories.ingredient_id', '=', 'ingredients.ingredient_id')
                ->selectRaw('COALESCE(SUM(inventories.quantity * ingredients.purchase_price), 0) as amount')
                ->value('amount');

            $movementTotals = StockMovement::query()
                ->where('movement_date', '>=', $today->copy()->subDays(6))
                ->selectRaw('DATE(movement_date) as movement_day')
                ->selectRaw('COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) as stock_in')
                ->selectRaw('COALESCE(SUM(CASE WHEN quantity < 0 THEN -quantity ELSE 0 END), 0) as stock_out')
                ->groupByRaw('DATE(movement_date)')
                ->get()
                ->keyBy('movement_day');

            $stockMovementOverview = [];

            for ($day = $today->copy()->subDays(6); $day->lessThanOrEqualTo($today); $day->addDay()) {
                $movement = $movementTotals->get($day->toDateString());
                $stockMovementOverview[] = [
                    'label' => $day->format('D'),
                    'stock_in' => (float) ($movement?->stock_in ?? 0),
                    'stock_out' => (float) ($movement?->stock_out ?? 0),
                ];
            }

            $viewData['inventoryOverview'] = [
                'products' => Product::count(),
                'categories' => InventoryCategory::count(),
                'tracked_items' => (int) $inventoryOverview->tracked_items,
                'total_stock' => (float) $inventoryOverview->total_stock,
                'low_stock' => (int) $inventoryOverview->low_stock,
                'out_of_stock' => (int) $inventoryOverview->out_of_stock,
                'valuation' => (float) $inventoryValue,
            ];
            $viewData['stockMovementOverview'] = $stockMovementOverview;
            $viewData['recentInventoryActivities'] = StockMovement::query()
                ->with(['ingredient', 'user'])
                ->orderByDesc('movement_date')
                ->orderByDesc('movement_id')
                ->limit(6)
                ->get();
        }

        if ($canViewSalesComparison) {
            $defaultWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
            $filters = $request->validate([
                'start_date' => ['required_with:end_date', 'date_format:Y-m-d', 'before_or_equal:today'],
                'end_date' => ['required_with:start_date', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:start_date'],
            ]);

            $currentStart = isset($filters['start_date'])
                ? Carbon::parse($filters['start_date'])->startOfDay()
                : $defaultWeekStart;
            $currentEnd = isset($filters['end_date'])
                ? Carbon::parse($filters['end_date'])->startOfDay()
                : $today->copy();
            $periodLength = (int) $currentStart->diffInDays($currentEnd) + 1;
            $previousEnd = $currentStart->copy()->subDay();
            $previousStart = $previousEnd->copy()->subDays($periodLength - 1);

            $salesByDate = Payment::query()
                ->where('payment_date', '>=', $previousStart)
                ->where('payment_date', '<', $currentEnd->copy()->addDay())
                ->selectRaw('DATE(payment_date) as sales_date, SUM(amount_paid - change_amount) as net_sales')
                ->groupByRaw('DATE(payment_date)')
                ->pluck('net_sales', 'sales_date');

            $currentBuckets = $this->salesBuckets($currentStart, $currentEnd, $salesByDate);
            $previousBuckets = $this->salesBuckets($previousStart, $previousEnd, $salesByDate);
            $bucketCount = max(count($currentBuckets), count($previousBuckets));
            $salesComparison = [];
            $currentSalesTotal = 0.0;
            $previousSalesTotal = 0.0;

            for ($index = 0; $index < $bucketCount; $index++) {
                $currentBucket = $currentBuckets[$index] ?? ['label' => '—', 'sales' => 0.0];
                $previousBucket = $previousBuckets[$index] ?? ['label' => '—', 'sales' => 0.0];
                $currentSalesTotal += $currentBucket['sales'];
                $previousSalesTotal += $previousBucket['sales'];

                $salesComparison[] = [
                    'label' => $currentBucket['label'],
                    'current_label' => $currentBucket['label'],
                    'previous_label' => $previousBucket['label'],
                    'current_sales' => $currentBucket['sales'],
                    'previous_sales' => $previousBucket['sales'],
                ];
            }

            $salesChartMax = max(
                1,
                max(array_column($salesComparison, 'current_sales')),
                max(array_column($salesComparison, 'previous_sales')),
            );

            $viewData += [
                'salesComparison' => $salesComparison,
                'currentSalesTotal' => $currentSalesTotal,
                'previousSalesTotal' => $previousSalesTotal,
                'salesChartMax' => $salesChartMax,
                'currentRangeLabel' => 'Selected dates ('.$this->formatRange($currentStart, $currentEnd).')',
                'previousRangeLabel' => $this->formatRange($previousStart, $previousEnd),
                'startDate' => $currentStart->toDateString(),
                'endDate' => $currentEnd->toDateString(),
            ];
        }

        return view('dashboard', $viewData);
    }

    /**
     * @return array<int, array{label: string, sales: float}>
     */
    private function salesBuckets(Carbon $start, Carbon $end, Collection $salesByDate): array
    {
        $buckets = [];

        for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            $key = $date->toDateString();
            $buckets[$key] ??= [
                'label' => $date->format('M j'),
                'sales' => 0.0,
            ];
            $buckets[$key]['sales'] += (float) $salesByDate->get($date->toDateString(), 0);
        }

        return array_values($buckets);
    }

    private function formatRange(Carbon $start, Carbon $end): string
    {
        return $start->format('M j, Y').' – '.$end->format('M j, Y');
    }
}
