@extends('layouts.app')

@section('title', 'Order #' . $order->order_id . ' Confirmed')
@section('page-title', 'Order #' . $order->order_id)
@section('page-subtitle', 'Order confirmed and stock deducted from inventory.')

@section('content')

@php
    $isWaitingForFreshChicken = $order->status === 'Pending' && $order->is_fresh_chicken;
    $payment = $order->payments->first();

    $statusStyles = [
        'Completed' => 'bg-slate-200 text-slate-700 border-slate-300',
        'Pending'   => 'bg-amber-100 text-amber-700 border-amber-200',
        'Ready'     => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    ];
    $badge = $statusStyles[$order->status] ?? $statusStyles['Pending'];
@endphp

<div class="mx-auto max-w-2xl space-y-5">

    {{-- ============ CONFIRMATION BANNER ============ --}}
    <div class="rounded-2xl border-2 p-8 text-center
                {{ $isWaitingForFreshChicken
                    ? 'border-amber-300 bg-amber-50'
                    : 'border-emerald-300 bg-emerald-50' }}">
        <div class="text-6xl" aria-hidden="true">{{ $isWaitingForFreshChicken ? '⏳' : '✅' }}</div>

        <h2 class="mt-4 text-2xl font-extrabold
                   {{ $isWaitingForFreshChicken ? 'text-amber-900' : 'text-emerald-900' }}">
            {{ $isWaitingForFreshChicken
                ? 'Order Waiting for Fresh Chicken'
                : 'Order Completed' }}
        </h2>

        <p class="mt-2 text-sm {{ $isWaitingForFreshChicken ? 'text-amber-800' : 'text-emerald-800' }}">
            @if ($isWaitingForFreshChicken)
                The stock was deducted and the payment was recorded. The kitchen must finish
                cooking fresh chicken before this order can be handed over.
            @else
                The stock was deducted and the payment was recorded successfully.
            @endif
        </p>

        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border-2 px-4 py-1.5 text-sm font-bold {{ $badge }}">{{ $order->status }}</span>
        </div>
    </div>

    {{-- ============ FRESH CHICKEN TIMER ============ --}}
    @if ($isWaitingForFreshChicken)
        <div class="rounded-2xl border border-amber-200 bg-white p-6">
            <h3 class="font-bold text-amber-900">Kitchen fresh chicken timer</h3>

            @if ($chickenBatches->isNotEmpty())
                <p class="mt-1 text-sm text-slate-600">
                    Chicken is being cooked. This order is released when the timer finishes
                    and the kitchen confirms the batch.
                </p>
                <div class="mt-3 space-y-3">
                    @foreach ($chickenBatches as $batch)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-amber-50 px-4 py-3">
                            <span class="text-sm font-semibold text-slate-800">{{ $batch->quantity }} pcs chicken</span>
                            <span class="fresh-chicken-countdown font-mono text-lg font-bold tabular-nums text-amber-800"
                                  role="timer"
                                  data-started-at="{{ $batch->started_at->toIso8601String() }}"
                                  data-duration-minutes="{{ $batch->duration_minutes }}">--:--</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-1 text-sm text-slate-600">
                    Waiting for the kitchen to start a fresh chicken batch.
                    No cooking timer is running yet.
                </p>
            @endif

            <a href="{{ route('orders.show', $order) }}"
               class="mt-4 inline-block text-sm font-semibold text-[#E31E24] hover:underline">
                View order details →
            </a>
        </div>
    @endif

    {{-- ============ ORDER SUMMARY ============ --}}
    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
        <h3 class="font-bold text-slate-900">Order summary</h3>

        <div class="mt-4 space-y-3">
            @foreach ($order->orderItems as $item)
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="min-w-0 text-slate-700">
                        <span class="font-semibold text-slate-800">{{ $item->quantity }}×</span>
                        {{ $item->product?->product_name ?? 'Unavailable product' }}
                    </span>
                    <span class="shrink-0 font-bold text-slate-800">
                        ₱{{ number_format($item->subtotal, 2) }}
                    </span>
                </div>
            @endforeach
        </div>

        <div class="mt-5 space-y-2 border-t border-slate-100 pt-4 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Service type</span>
                <span class="font-semibold text-slate-800">{{ $order->order_type }}</span>
            </div>
            @if ($payment)
                <div class="flex justify-between">
                    <span class="text-slate-500">Payment method</span>
                    <span class="font-semibold text-slate-800">{{ $payment->payment_method }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Amount received</span>
                    <span class="font-semibold text-slate-800">₱{{ number_format($payment->amount_paid, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Change</span>
                    <span class="font-semibold text-emerald-600">₱{{ number_format($payment->change_amount, 2) }}</span>
                </div>
            @endif
            <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                <span class="text-lg font-bold text-slate-700">Total</span>
                <span class="text-2xl font-extrabold text-[#E31E24]">
                    ₱{{ number_format($order->total_amount, 2) }}
                </span>
            </div>
        </div>
    </div>

    {{-- ============ NEXT ACTION ============ --}}
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('orders.index') }}"
           class="rounded-xl border border-slate-200 px-5 py-3 text-center font-semibold text-slate-700 transition hover:bg-slate-50">
            Go to Orders
        </a>
        <a href="{{ route('orders.create') }}"
           class="rounded-xl bg-[#E31E24] px-6 py-3 text-center font-bold text-white shadow-lg shadow-red-500/20 transition hover:bg-red-700">
            Create Another Order
        </a>
    </div>
</div>

@push('scripts')
<script>
(() => {
    function updateChickenCountdowns() {
        document.querySelectorAll('.fresh-chicken-countdown').forEach((timer) => {
            const endTime = Date.parse(timer.dataset.startedAt) + Number(timer.dataset.durationMinutes) * 60_000;
            const remainingSeconds = Math.max(0, Math.ceil((endTime - Date.now()) / 1000));
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            timer.textContent = remainingSeconds > 0
                ? `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
                : 'Timer complete — awaiting kitchen confirmation';
        });
    }

    updateChickenCountdowns();
    window.setInterval(updateChickenCountdowns, 1000);
})();
</script>
@endpush

@endsection
