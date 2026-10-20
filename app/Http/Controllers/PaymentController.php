<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PaymentController extends Controller
{
    /**
     * Display a list of payments.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->employee?->role?->role_name;

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        $filterDate = isset($validated['date'])
            ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
            : null;

        $query = Payment::with([
            'order.employee',
        ])
            ->orderByDesc('payment_date')
            ->orderByDesc('payment_id');

        if ($filterDate) {
            $startOfNextDay = $filterDate->copy()->addDay();

            $query->where('payment_date', '>=', $filterDate)
                ->where('payment_date', '<', $startOfNextDay);
        }

        /*
        |--------------------------------------------------------------------------
        | Manager
        |--------------------------------------------------------------------------
        | Manager can see all payments.
        |
        |--------------------------------------------------------------------------
        | Cashier
        |--------------------------------------------------------------------------
        | Cashier can only see payments belonging to their own orders.
        */
        if ($role !== 'Manager') {
            $query->whereHas('order', function ($orderQuery) use ($user) {
                $orderQuery->where(
                    'employee_id',
                    $user->employee_id
                );
            });
        }

        $methodFilter = request()->query('method');

        if (! in_array($methodFilter, ['Cash', 'GCash'], true)) {
            $methodFilter = 'All';
        } else {
            $query->where('payment_method', $methodFilter);
        }

        $payments = $query->get();
        $allPaymentsCount = Payment::query()
            ->when($filterDate, function ($paymentQuery) use ($filterDate) {
                $paymentQuery->where('payment_date', '>=', $filterDate)
                    ->where('payment_date', '<', $filterDate->copy()->addDay());
            })
            ->when($role !== 'Manager', function ($paymentQuery) use ($user) {
                $paymentQuery->whereHas('order', function ($orderQuery) use ($user) {
                    $orderQuery->where('employee_id', $user->employee_id);
                });
            })
            ->count();

        return view('payments.index', compact(
            'payments',
            'methodFilter',
            'allPaymentsCount',
            'filterDate'
        ));
    }

    /**
     * Show the form for creating a payment.
     */
    public function create(Request $request)
    {
        $orderId = $request->query('order_id');

        if (! $orderId) {
            return redirect()
                ->route('orders.index')
                ->withErrors([
                    'payment' => 'No order was selected for payment.',
                ]);
        }

        $order = Order::with([
            'employee',
            'orderItems.product',
            'payments',
        ])->findOrFail($orderId);

        $this->authorizePayment($order);

        /*
        |--------------------------------------------------------------------------
        | Payment status check
        |--------------------------------------------------------------------------
        */
        if ($order->status === 'Paid') {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors([
                    'payment' => 'This order has already been paid.',
                ]);
        }

        if ($order->status === 'Completed') {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors([
                    'payment' => 'This order has already been completed.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate payments
        |--------------------------------------------------------------------------
        */
        if ($order->payments->isNotEmpty()) {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors([
                    'payment' => 'This order already has a payment record.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Only Ready orders can be paid
        |--------------------------------------------------------------------------
        */
        if ($order->status !== 'Ready') {
            return redirect()
                ->route('orders.show', $order)
                ->withErrors([
                    'payment' => 'Only orders marked as Ready can be paid.',
                ]);
        }

        return view('payments.create', compact('order'));
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => [
                'required',
                'exists:orders,order_id',
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

        $order = Order::with('payments')
            ->findOrFail($validated['order_id']);

        $this->authorizePayment($order);

        /*
        |--------------------------------------------------------------------------
        | Check order status
        |--------------------------------------------------------------------------
        */
        if ($order->status !== 'Ready') {
            return back()
                ->withErrors([
                    'payment' => 'Only orders marked as Ready can be paid.',
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate payments
        |--------------------------------------------------------------------------
        */
        if ($order->payments->isNotEmpty()) {
            return back()
                ->withErrors([
                    'payment' => 'This order already has a payment record.',
                ])
                ->withInput();
        }

        $totalAmount = (float) $order->total_amount;

        $amountPaid = (float) $validated['amount_paid'];

        /*
        |--------------------------------------------------------------------------
        | Check amount paid
        |--------------------------------------------------------------------------
        */
        if ($amountPaid < $totalAmount) {

            return back()
                ->withErrors([
                    'amount_paid' => 'Payment is insufficient. '
                        .'Order total is ₱'
                        .number_format($totalAmount, 2)
                        .'.',
                ])
                ->withInput();
        }

        if (
            $validated['payment_method'] === 'GCash'
            && round($amountPaid * 100) !== round($totalAmount * 100)
        ) {
            return back()
                ->withErrors([
                    'amount_paid' => 'GCash payment must match the order total exactly.',
                ])
                ->withInput();
        }

        $changeAmount = $amountPaid - $totalAmount;

        /*
        |--------------------------------------------------------------------------
        | Create payment
        |--------------------------------------------------------------------------
        */
        $proofPath = null;

        try {
            DB::transaction(function () use (
                $order,
                $validated,
                $amountPaid,
                $changeAmount,
                $request,
                &$proofPath,
            ) {
                if ($validated['payment_method'] === 'GCash') {
                    $proofPath = $request->file('gcash_proof')?->store('gcash-proofs', 'local');

                    if (! $proofPath) {
                        throw new RuntimeException('The GCash payment proof could not be saved.');
                    }
                }

                Payment::create([
                    'order_id' => $order->order_id,
                    'payment_method' => $validated['payment_method'],
                    'amount_paid' => $amountPaid,
                    'change_amount' => $changeAmount,
                    'payment_date' => now(),
                    'gcash_proof_path' => $proofPath,
                ]);

                $order->update([
                    'status' => 'Paid',
                ]);
            });
        } catch (Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'success',
                'Payment recorded successfully. Order is now marked as Paid.'
            );
    }

    /**
     * Display a specific payment.
     */
    public function show(Payment $payment)
    {
        $this->authorizePayment($payment->order);

        $payment->load([
            'order.employee',
            'order.orderItems.product',
        ]);

        return view('payments.show', compact('payment'));
    }

    public function proof(Payment $payment)
    {
        $this->authorizePayment($payment->order);

        abort_unless(
            $payment->gcash_proof_path
                && Storage::disk('local')->exists($payment->gcash_proof_path),
            404
        );

        return Storage::disk('local')->response($payment->gcash_proof_path);
    }

    /**
     * Payment editing is intentionally disabled.
     */
    public function edit(Payment $payment)
    {
        abort(
            403,
            'Payment records cannot be edited.'
        );
    }

    /**
     * Payment updating is intentionally disabled.
     */
    public function update(Request $request, Payment $payment)
    {
        abort(
            403,
            'Payment records cannot be edited.'
        );
    }

    /**
     * Delete a payment.
     */
    public function destroy(Payment $payment)
    {
        $this->authorizePayment($payment->order);

        abort(
            403,
            'Payment records cannot be deleted.'
        );
    }

    /**
     * Authorize access to a payment/order.
     */
    private function authorizePayment(Order $order): void
    {
        $user = auth()->user();

        $role = $user->employee?->role?->role_name;

        /*
        |--------------------------------------------------------------------------
        | Manager
        |--------------------------------------------------------------------------
        */
        if ($role === 'Manager') {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Cashier
        |--------------------------------------------------------------------------
        */
        if ($role === 'Cashier') {

            if ($order->employee_id !== $user->employee_id) {

                abort(
                    403,
                    'You are not authorized to access this payment.'
                );
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Other roles
        |--------------------------------------------------------------------------
        */
        abort(
            403,
            'You are not authorized to manage payments.'
        );
    }
}
