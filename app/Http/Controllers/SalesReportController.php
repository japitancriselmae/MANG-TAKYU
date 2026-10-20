<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SalesReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
        ]);

        $reportDate = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
            : Carbon::today();

        $startOfDay = $reportDate->copy()->startOfDay();
        $startOfNextDay = $startOfDay->copy()->addDay();

        $payments = Payment::query()
            ->where('payment_date', '>=', $startOfDay)
            ->where('payment_date', '<', $startOfNextDay)
            ->orderBy('payment_date')
            ->orderBy('payment_id')
            ->get();

        $transactionCount = $payments->count();
        $netSales = $this->sumNetSales($payments);
        $averageOrder = $transactionCount > 0 ? $netSales / $transactionCount : 0.0;

        $topItems = $this->topSellingItems($startOfDay, $startOfNextDay);

        return view(
            'reports.sales',
            [
                'payments' => $payments,
                'reportDate' => $startOfDay,
                'transactionCount' => $transactionCount,
                'netSales' => $netSales,
                'averageOrder' => $averageOrder,
                'topItems' => $topItems,
            ]
        );
    }

    /**
     * Sum net sales (amount collected minus change given back).
     */
    private function sumNetSales(Collection $payments): float
    {
        return (float) $payments->sum(
            fn (Payment $payment): float => (float) $payment->amount_paid
                - (float) $payment->change_amount
        );
    }

    /**
     * Top selling products for the day, ranked by quantity sold.
     */
    private function topSellingItems(Carbon $startOfDay, Carbon $startOfNextDay): Collection
    {
        $items = OrderItem::query()
            ->with('product')
            ->whereHas('order', fn ($query) => $query
                ->where('order_date', '>=', $startOfDay)
                ->where('order_date', '<', $startOfNextDay))
            ->get()
            ->groupBy(fn (OrderItem $item) => $item->product_id)
            ->map(function (Collection $group, string $productId): array {
                $product = $group->first()->product;
                $quantity = $group->sum('quantity');

                return [
                    'product' => $product,
                    'quantity' => (int) $quantity,
                ];
            })
            ->sortByDesc(fn (array $item): int => $item['quantity'])
            ->values();

        $totalQuantity = $items->sum('quantity');

        return $items->map(function (array $item) use ($totalQuantity): array {
            $percentage = $totalQuantity > 0
                ? round(($item['quantity'] / $totalQuantity) * 100)
                : 0;

            return [
                'product' => $item['product'],
                'quantity' => $item['quantity'],
                'percentage' => $percentage,
            ];
        });
    }
}
