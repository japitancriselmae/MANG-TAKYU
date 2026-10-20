<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionReportController extends Controller
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
            ->with([
                'order.employee',
                'order.orderItems.product',
            ])
            ->where('payment_date', '>=', $startOfDay)
            ->where('payment_date', '<', $startOfNextDay)
            ->orderBy('payment_date')
            ->orderBy('payment_id')
            ->get();

        return view(
            'reports.transactions',
            [
                'payments' => $payments,
                'reportDate' => $startOfDay,
                'transactionCount' => $payments->count(),
                'netSales' => $this->sumNetSales($payments),
                'cashSales' => $this->sumNetSales(
                    $payments->where('payment_method', 'Cash')
                ),
                'gcashSales' => $this->sumNetSales(
                    $payments->where('payment_method', 'GCash')
                ),
                'totalCollected' => (float) $payments->sum(
                    fn (Payment $payment): float => (float) $payment->amount_paid
                ),
                'totalChangeGiven' => (float) $payments->sum(
                    fn (Payment $payment): float => (float) $payment->change_amount
                ),
            ]
        );
    }

    /**
     * Sum the net sales of a payment set, where net sales are the amount
     * collected minus the change handed back to the customer.
     */
    private function sumNetSales(Collection $payments): float
    {
        return (float) $payments->sum(
            fn (Payment $payment): float => (float) $payment->amount_paid
                - (float) $payment->change_amount
        );
    }
}
