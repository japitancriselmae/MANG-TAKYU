@extends('layouts.app')

@section('title', 'Order #' . $order->order_id)
@section('page-title', 'Order #' . $order->order_id)
@section('page-subtitle', 'Order details and status timeline.')

@section('content')

@php
    $user = auth()->user();
    $role = $user->employee?->role?->role_name;

    $steps = $order->status === 'Paid'
        ? ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Paid', 'Completed']
        : ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Completed'];
    $currentIndex = array_search($order->status, $steps);
    if ($currentIndex === false) $currentIndex = 0;

    $statusStyles = [
        'Pending'   => 'bg-amber-100 text-amber-700 border-amber-200',
        'Confirmed' => 'bg-blue-100 text-blue-700 border-blue-200',
        'Preparing' => 'bg-purple-100 text-purple-700 border-purple-200',
        'Ready'     => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'Paid'      => 'bg-teal-100 text-teal-700 border-teal-200',
        'Completed' => 'bg-slate-200 text-slate-700 border-slate-300',
    ];
    $badge = $statusStyles[$order->status] ?? $statusStyles['Pending'];
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full border-2 font-bold text-sm {{ $badge }}">
            {{ $order->status }}
        </span>
        @if ($order->is_fresh_chicken)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#F5A623]/20 text-[#b5790b] text-xs font-bold">
                🍗 Fresh Chicken
            </span>
        @endif
    </div>

    <div class="flex gap-2">
        <a href="{{ route('orders.index') }}"
           class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition text-sm">
            ← All Orders
        </a>
        @if ($order->status !== 'Completed')
            <a href="{{ route('orders.edit', $order) }}"
               class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition text-sm">
                Edit Order
            </a>
        @endif
    </div>
</div>

{{-- FRESH CHICKEN TIMER --}}
@if ($order->is_fresh_chicken && $order->status !== 'Completed')
    <div class="rounded-2xl border border-amber-200 bg-white p-6 mb-5">
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
    </div>
@endif

{{-- TIMELINE --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-5">
    <div class="flex items-center justify-between gap-1 overflow-x-auto pb-2">
        @foreach ($steps as $i => $step)
            @php
                $done    = $i < $currentIndex;
                $current = $i === $currentIndex;
            @endphp
            <div class="flex items-center gap-3 shrink-0">
                <div class="flex flex-col items-center gap-2">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2
                                {{ $done ? 'bg-emerald-500 border-emerald-500 text-white'
                                         : ($current ? 'bg-[#E31E24] border-[#E31E24] text-white animate-pulse'
                                                     : 'bg-white border-slate-200 text-slate-400') }}">
                        @if ($done) ✓ @else {{ $i + 1 }} @endif
                    </div>
                    <div class="text-xs font-semibold whitespace-nowrap
                                {{ $current ? 'text-[#E31E24]' : ($done ? 'text-emerald-600' : 'text-slate-400') }}">
                        {{ $step }}
                    </div>
                </div>
                @if ($i < count($steps) - 1)
                    <div class="w-8 sm:w-16 h-0.5 mb-6 {{ $i < $currentIndex ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- INFO GRID --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Order Type</div>
        <div class="text-lg font-bold text-slate-800">{{ $order->order_type }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Payment</div>
        @if ($order->payments->isNotEmpty())
            <div class="text-lg font-bold text-emerald-700">Paid · {{ $order->payments->first()->payment_method }}</div>
            <div class="text-xs text-slate-500">₱{{ number_format($order->payments->first()->amount_paid, 2) }}</div>
        @else
            <div class="text-lg font-bold text-amber-700">Not paid</div>
        @endif
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Date</div>
        <div class="text-lg font-bold text-slate-800">{{ $order->order_date->format('M d, Y') }}</div>
        <div class="text-xs text-slate-500">{{ $order->order_date->format('h:i:s A') }}</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Taken By</div>
        <div class="text-lg font-bold text-slate-800">
            {{ $order->employee?->first_name }} {{ $order->employee?->last_name }}
        </div>
        <div class="text-xs text-slate-500">{{ $order->employee?->role?->role_name ?? '' }}</div>
    </div>
</div>

{{-- ITEMS --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-5">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="font-bold text-slate-800">Order Items</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-6 py-3 font-semibold">Product</th>
                    <th class="px-6 py-3 font-semibold text-center">Qty</th>
                    <th class="px-6 py-3 font-semibold text-right">Unit Price</th>
                    <th class="px-6 py-3 font-semibold text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($order->orderItems as $item)
                    <tr>
                        <td class="px-6 py-4 font-semibold text-slate-800">
                            {{ $item->product?->product_name ?? 'Product Deleted' }}
                        </td>
                        <td class="px-6 py-4 text-center text-slate-700">{{ $item->quantity }}</td>
                        <td class="px-6 py-4 text-right text-slate-600">₱{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-6 py-4 text-right font-bold text-slate-800">₱{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">No items.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 border-t border-slate-200">
                <tr>
                    <td colspan="3" class="px-6 py-5 text-right text-base font-bold text-slate-700">Total</td>
                    <td class="px-6 py-5 text-right text-2xl font-bold text-[#E31E24]">₱{{ number_format($order->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- PAYMENT INFO --}}
@if ($order->payments->count() > 0)
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-5">
        <h3 class="font-bold text-slate-800 mb-4">Payment Information</h3>
        @foreach ($order->payments as $payment)
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Method</div>
                    <div class="font-bold text-slate-800">{{ $payment->payment_method }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Paid</div>
                    <div class="font-bold text-slate-800">₱{{ number_format($payment->amount_paid, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Change</div>
                    <div class="font-bold text-emerald-600">₱{{ number_format($payment->change_amount, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Date</div>
                    <div class="font-semibold text-slate-700 text-sm">{{ $payment->payment_date->format('M d, Y h:i A') }}</div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- ACTIONS --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
    <h3 class="font-bold text-slate-800 mb-4">Actions</h3>
    <div class="flex flex-wrap gap-3">
        @if ($order->status === 'Pending')
            <form action="{{ route('orders.confirm', $order) }}" method="POST">
                @csrf
                <button type="submit"
                        onclick="return confirm('Confirm this order? Ingredients will be deducted from inventory.')"
                        class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold shadow-lg shadow-blue-500/20 transition">
                    Confirm Order
                </button>
            </form>
        @endif

        @if ($order->status === 'Confirmed' && in_array($role, ['Manager', 'Kitchen Staff']))
            <form action="{{ route('orders.preparing', $order) }}" method="POST">
                @csrf
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold shadow-lg shadow-purple-500/20 transition">
                    Start Preparing
                </button>
            </form>
        @endif

        @if ($order->status === 'Preparing' && in_array($role, ['Manager', 'Kitchen Staff']))
            <form action="{{ route('orders.ready', $order) }}" method="POST">
                @csrf
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-lg shadow-emerald-500/20 transition">
                    Mark as Ready
                </button>
            </form>
        @endif

        @if ($order->status === 'Ready' && $order->payments->isEmpty())
            <a href="{{ route('payments.create', ['order_id' => $order->order_id]) }}"
               class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-bold shadow-lg shadow-red-500/20 transition">
                Record Payment
            </a>
        @endif

        @if (in_array($order->status, ['Paid', 'Ready']) && $order->payments->isNotEmpty())
            <form action="{{ route('orders.complete', $order) }}" method="POST">
                @csrf
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-bold shadow-lg shadow-slate-500/20 transition">
                    Fully Complete Order
                </button>
            </form>
        @endif

        @if ($order->status === 'Completed')
            <div class="px-6 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold">
                ✓ This order is complete
            </div>
        @endif
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