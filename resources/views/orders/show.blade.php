@extends('layouts.app')

@section('title', 'Order #' . $order->order_id)
@section('page-title', 'Order #' . $order->order_id)
@section('page-subtitle', 'Order details and status timeline.')

@section('content')

@php
    $user = auth()->user();
    $role = $user->employee?->role?->role_name;

    $steps = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Paid', 'Completed'];
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
        @if ($order->status === 'Pending')
            <a href="{{ route('orders.edit', $order) }}"
               class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition text-sm">
                Edit Order
            </a>
        @endif
    </div>
</div>

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

{{-- FRESH CHICKEN TIMER --}}
@if ($order->is_fresh_chicken)
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-2xl">🍗</span>
                    <h3 class="font-bold text-slate-800">Fresh Chicken Order</h3>
                </div>
                <p class="text-sm text-slate-500">12-minute preparation timer</p>
                @if ($order->estimated_ready_at)
                    <p class="text-xs text-slate-500 mt-1">
                        Estimated ready: <span class="font-semibold text-slate-700">{{ $order->estimated_ready_at->format('h:i:s A') }}</span>
                    </p>
                @endif
            </div>

            @if ($order->ready_at)
                <div class="px-8 py-5 bg-emerald-50 border border-emerald-200 rounded-2xl text-center">
                    <div class="text-xs uppercase tracking-widest text-emerald-700 font-bold mb-1">Ready</div>
                    <div class="text-5xl text-emerald-600">✓</div>
                    <div class="text-xs text-emerald-600 mt-1">at {{ $order->ready_at->format('h:i:s A') }}</div>
                </div>
            @elseif ($order->estimated_ready_at)
                <div id="timerBox" class="px-8 py-5 bg-blue-50 border border-blue-200 rounded-2xl text-center">
                    <div id="timerLabel" class="text-xs uppercase tracking-widest text-blue-700 font-bold mb-1">Time Remaining</div>
                    <div id="countdown" class="text-5xl font-bold text-blue-700 tabular-nums">--:--</div>
                    <div id="timerMessage" class="text-xs text-blue-600 mt-1">Preparing fresh chicken...</div>
                </div>
            @else
                <div class="px-8 py-5 bg-amber-50 border border-amber-200 rounded-2xl text-center">
                    <div class="text-xs uppercase tracking-widest text-amber-700 font-bold mb-1">Not Started</div>
                    <div class="text-3xl font-bold text-amber-700">12:00</div>
                    <div class="text-xs text-amber-600 mt-1">Awaiting confirmation</div>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- INFO GRID --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Order Type</div>
        <div class="text-lg font-bold text-slate-800">{{ $order->order_type }}</div>
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

        @if ($order->status === 'Ready')
            <a href="{{ route('payments.create', ['order_id' => $order->order_id]) }}"
               class="px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-bold shadow-lg shadow-red-500/20 transition">
                Record Payment
            </a>
        @endif

        @if ($order->status === 'Paid')
            <form action="{{ route('orders.complete', $order) }}" method="POST">
                @csrf
                <button type="submit"
                        class="px-6 py-3 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-bold shadow-lg shadow-slate-500/20 transition">
                    Complete Order
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
@if ($order->is_fresh_chicken && $order->estimated_ready_at && !$order->ready_at)
<script>
const estimatedReadyAt = new Date(@json($order->estimated_ready_at->toIso8601String())).getTime();
const countdown  = document.getElementById('countdown');
const timerLabel = document.getElementById('timerLabel');
const timerMsg   = document.getElementById('timerMessage');
const timerBox   = document.getElementById('timerBox');

function tick() {
    const diff = estimatedReadyAt - Date.now();
    if (diff <= 0) {
        countdown.textContent = '00:00';
        timerLabel.textContent = 'Time Reached';
        timerMsg.textContent = 'The 12-minute prep time has ended.';
        timerBox.classList.remove('bg-blue-50', 'border-blue-200');
        timerBox.classList.add('bg-amber-50', 'border-amber-300');
        return;
    }
    const totalSec = Math.floor(diff / 1000);
    const m = String(Math.floor(totalSec / 60)).padStart(2, '0');
    const s = String(totalSec % 60).padStart(2, '0');
    countdown.textContent = m + ':' + s;
}
tick();
setInterval(tick, 1000);
</script>
@endif
@endpush

@endsection