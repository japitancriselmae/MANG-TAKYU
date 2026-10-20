<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $roleName = $request->user()->employee?->role?->role_name;
        $canViewSalesComparison = in_array($roleName, ['Manager', 'Admin'], true);
        $today = Carbon::today();

        $startOfDay = Carbon::today();
        $startOfTomorrow = $startOfDay->copy()->addDay();

        $ordersToday = Order::query()
            ->where('order_date', '>=', $startOfDay)
            ->where('order_date', '<', $startOfTomorrow)
            ->selectRaw(
                'COUNT(*) as total_orders, '.
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_orders, '.
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_orders',
                ['Pending', 'Completed'],
            )
            ->firstOrFail();

        $paymentsToday = Payment::query()
            ->where('payment_date', '>=', $startOfDay)
            ->where('payment_date', '<', $startOfTomorrow)
            ->selectRaw(
                'COUNT(*) as total_transactions, '.
                'COALESCE(SUM(amount_paid - change_amount), 0) as sales_today',
            )
            ->firstOrFail();

        $viewData = [
            'totalOrders' => (int) $ordersToday->total_orders,
            'pendingOrders' => (int) $ordersToday->pending_orders,
            'completedOrders' => (int) $ordersToday->completed_orders,
            'totalTransactions' => (int) $paymentsToday->total_transactions,
            'salesToday' => (float) $paymentsToday->sales_today,
            'canViewSalesComparison' => $canViewSalesComparison,
        ];

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
