@extends('layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')
@section('page-subtitle', 'View customer orders and order status.')

@section('content')

@php
    $user = auth()->user();
    $role = $user->employee?->role?->role_name;
    $pendingCount = $pendingOrdersCount;

    $statusStyles = [
        'Pending'   => ['bg' => 'bg-amber-100',   'text' => 'text-amber-700',   'dot' => 'bg-amber-500'],
        'Confirmed' => ['bg' => 'bg-blue-100',    'text' => 'text-blue-700',    'dot' => 'bg-blue-500'],
        'Preparing' => ['bg' => 'bg-purple-100',  'text' => 'text-purple-700',  'dot' => 'bg-purple-500'],
        'Ready'     => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
        'Paid'      => ['bg' => 'bg-teal-100',    'text' => 'text-teal-700',    'dot' => 'bg-teal-500'],
        'Completed' => ['bg' => 'bg-slate-200',   'text' => 'text-slate-700',   'dot' => 'bg-slate-500'],
    ];
@endphp

{{-- KPI CARDS --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Total Orders</div>
        <div class="text-3xl font-bold text-slate-800">{{ $orders->count() }}</div>
    </div>
</div>

{{-- ACTIONS BAR --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h3 class="text-lg font-bold text-slate-800">Order List</h3>
    </div>
    @if ($role === 'Cashier')
        <a href="{{ route('orders.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E31E24] hover:bg-red-700
                  text-white font-semibold shadow-lg shadow-red-500/20 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Order
        </a>
    @endif
</div>

{{-- TABLE --}}
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
    @if ($orders->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr class="text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-6 py-3 font-semibold">Order</th>
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold">Employee</th>
                        <th class="px-6 py-3 font-semibold">Type</th>
                        <th class="px-6 py-3 font-semibold">Items</th>
                        <th class="px-6 py-3 font-semibold">Total</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        @if ($role === 'Cashier')
                            <th class="px-6 py-3 font-semibold text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        @php $style = $statusStyles[$order->status] ?? $statusStyles['Pending']; @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800">#{{ $order->order_id }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div class="font-medium text-slate-700">
                                    {{ \Carbon\Carbon::parse($order->order_date)->format('M d, Y') }}
                                </div>
                                <div class="text-slate-400">
                                    {{ \Carbon\Carbon::parse($order->order_date)->format('h:i A') }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">
                                    {{ $order->employee?->first_name }} {{ $order->employee?->last_name }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $order->employee?->role?->role_name ?? '' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($order->order_type === 'Dine-in')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">Dine-in</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700">Take-out</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                <span class="font-semibold">{{ $order->orderItems->sum('quantity') }}</span>
                                <span class="text-xs text-slate-500">item(s)</span>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800">
                                ₱{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                <button type="button"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $style['bg'] }} {{ $style['text'] }} hover:ring-2 hover:ring-slate-300"
                                        data-order-status-trigger
                                        data-order-id="{{ $order->order_id }}"
                                        data-order-status="{{ $order->status }}"
                                        data-order-fresh="{{ $order->is_fresh_chicken ? 'true' : 'false' }}"
                                        data-order-type="{{ $order->order_type }}"
                                        data-order-total="{{ number_format((float) $order->total_amount, 2, '.', '') }}"
                                        data-order-items="{{ $order->orderItems->sum('quantity') }}"
                                        data-order-payment="{{ $order->payments->first()?->payment_method ?? 'Not paid' }}"
                                        data-complete-url="{{ route('orders.complete', $order) }}"
                                        data-auto-open="{{ $autoOpenOrderId === $order->order_id ? 'true' : 'false' }}"
                                        aria-label="View status for order #{{ $order->order_id }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }}"></span>
                                    {{ $order->status }}
                                </button>
                            </td>
                            @if ($role === 'Cashier')
                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            data-order-view
                                            data-view-id="{{ $order->order_id }}"
                                            data-view-date="{{ $order->order_date->format('M d, Y h:i A') }}"
                                            data-view-status="{{ $order->status }}"
                                            data-view-type="{{ $order->order_type }}"
                                            data-view-method="{{ $order->payments->first()?->payment_method ?? 'Not paid' }}"
                                            data-view-total="{{ number_format((float) $order->total_amount, 2, '.', '') }}"
                                            data-view-paid="{{ number_format((float) $order->payments->sum('amount_paid'), 2, '.', '') }}"
                                            data-view-change="{{ number_format((float) $order->payments->sum('change_amount'), 2, '.', '') }}"
                                            @php
                                                $viewItems = $order->orderItems->map(fn ($item) => [
                                                    'name' => $item->product?->product_name ?? 'Unavailable product',
                                                    'qty' => $item->quantity,
                                                    'unit_price' => (float) $item->unit_price,
                                                    'subtotal' => (float) $item->subtotal,
                                                ])->values();
                                            @endphp
                                            data-view-items="{{ json_encode($viewItems) }}"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition">
                                            <svg class="w-3.5 h-3.5 inline-block -mt-0.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            View
                                        </button>
                                        @if ($order->status === 'Pending' && $order->payments->isEmpty())
                                            <a href="{{ route('orders.edit', $order) }}"
                                               class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition">
                                                Edit
                                            </a>
                                            <form action="{{ route('orders.confirm', $order) }}" method="POST"
                                                  onsubmit="return confirm('Confirm this order? Ingredients will be deducted from inventory.');">
                                                @csrf
                                                <button type="submit"
                                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                                    Confirm
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-20 text-center">
            <div class="text-6xl mb-4">🧾</div>
            <h3 class="text-xl font-bold text-slate-700">{{ $filter === 'pending' ? 'No pending orders' : 'No orders yet' }}</h3>
            <p class="text-sm text-slate-500 mt-2 mb-6">{{ $filter === 'pending' ? 'New orders will appear here.' : ($role === 'Cashier' ? 'Start by creating your first customer order.' : 'Orders placed by cashiers will appear here.') }}</p>
            @if ($role === 'Cashier')
                <a href="{{ route('orders.create') }}"
                   class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
                    Create First Order
                </a>
            @endif
        </div>
    @endif
</div>

<div id="orderStatusModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4"
     role="dialog" aria-modal="true" aria-labelledby="orderStatusModalTitle">
    <section class="w-full max-w-xl rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-5 sm:p-6">
            <div>
                <h2 id="orderStatusModalTitle" class="text-xl font-bold text-slate-900">Order Status</h2>
                <p id="orderStatusModalSubtitle" class="mt-1 text-sm text-slate-500"></p>
            </div>
            <button type="button" data-close-order-status
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                    aria-label="Close order status">✕</button>
        </div>

        <div class="space-y-5 p-5 sm:p-6">
            <div id="orderStatusTimeline" class="grid grid-cols-3 gap-2" aria-live="polite"></div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Order type</div>
                    <div id="orderStatusType" class="mt-1 font-bold text-slate-800"></div>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Items</div>
                    <div id="orderStatusItems" class="mt-1 font-bold text-slate-800"></div>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Payment</div>
                    <div id="orderStatusPayment" class="mt-1 font-bold text-slate-800"></div>
                </div>
                <div class="rounded-xl bg-slate-50 p-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total</div>
                    <div id="orderStatusTotal" class="mt-1 font-bold text-slate-800"></div>
                </div>
            </div>
            <p id="orderFreshChickenNote" class="hidden rounded-xl bg-amber-50 p-3 text-sm font-medium text-amber-800">
                Fresh chicken orders stay Pending until the kitchen confirms a chicken batch is ready.
            </p>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 p-5 sm:p-6">
            <button type="button" data-close-order-status
                    class="rounded-xl border border-slate-200 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">
                Close
            </button>
            <form id="completeOrderFromStatus" method="POST" class="hidden">
                @csrf
                <button type="submit"
                        class="rounded-xl bg-emerald-600 px-5 py-3 font-bold text-white hover:bg-emerald-700">
                    Mark Completed
                </button>
            </form>
        </div>
    </section>
</div>

{{-- ORDER VIEW MODAL --}}
<div id="orderViewModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm p-4">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h3 id="orderViewTitle" class="text-lg font-bold text-slate-800">Order Details</h3>
                <p id="orderViewSubtitle" class="text-sm text-slate-500 mt-1"></p>
            </div>
            <button type="button" data-close-order-view aria-label="Close"
                class="shrink-0 text-slate-400 hover:text-slate-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Order ID</div>
                <div id="orderViewId" class="font-bold text-slate-800"></div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Order Date</div>
                <div id="orderViewDate" class="font-bold text-slate-800"></div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Status</div>
                <div id="orderViewStatus" class="font-bold text-slate-800"></div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Order Type</div>
                <div id="orderViewType" class="font-bold text-slate-800"></div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Payment Method</div>
                <div id="orderViewMethod" class="font-bold text-slate-800"></div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Total</div>
                <div id="orderViewTotal" class="font-bold text-slate-800"></div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Amount Paid</div>
                <div id="orderViewPaid" class="font-bold text-slate-800"></div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Change</div>
                <div id="orderViewChange" class="font-bold text-emerald-600"></div>
            </div>
        </div>

        <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wider text-slate-400">
                        <th class="px-4 py-3 font-semibold">Item</th>
                        <th class="px-4 py-3 font-semibold text-right">Price</th>
                        <th class="px-4 py-3 font-semibold text-right">Qty</th>
                        <th class="px-4 py-3 font-semibold text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="orderViewItems" class="divide-y divide-slate-100"></tbody>
            </table>
            <div id="orderViewEmpty" class="hidden px-4 py-6 text-center text-sm text-slate-500">
                No items found for this order.
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const modal = document.getElementById('orderStatusModal');
    const title = document.getElementById('orderStatusModalTitle');
    const subtitle = document.getElementById('orderStatusModalSubtitle');
    const timeline = document.getElementById('orderStatusTimeline');
    const completeForm = document.getElementById('completeOrderFromStatus');
    const freshNote = document.getElementById('orderFreshChickenNote');
    const triggers = [...document.querySelectorAll('[data-order-status-trigger]')];

    function openOrderStatus(trigger) {
        const orderId = trigger.dataset.orderId;
        const status = trigger.dataset.orderStatus;
        const isFreshChicken = trigger.dataset.orderFresh === 'true';
        const usesLegacySteps = ['Confirmed', 'Preparing', 'Paid'].includes(status)
            || (!isFreshChicken && status === 'Ready');
        const steps = usesLegacySteps
            ? ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Paid', 'Completed']
            : (isFreshChicken ? ['Pending', 'Ready', 'Completed'] : ['Pending', 'Completed']);
        const currentIndex = Math.max(0, steps.indexOf(status));

        title.textContent = `Order #${orderId} Status`;
        subtitle.textContent = `Current status: ${status}${isFreshChicken ? ' · Fresh chicken' : ''}`;
        document.getElementById('orderStatusType').textContent = trigger.dataset.orderType;
        document.getElementById('orderStatusItems').textContent = `${trigger.dataset.orderItems} item(s)`;
        document.getElementById('orderStatusPayment').textContent = trigger.dataset.orderPayment;
        document.getElementById('orderStatusTotal').textContent = `₱${Number(trigger.dataset.orderTotal).toFixed(2)}`;
        freshNote.classList.toggle('hidden', !isFreshChicken);

        timeline.replaceChildren();
        steps.forEach((step, index) => {
            const isCurrent = index === currentIndex;
            const isComplete = index < currentIndex;
            const item = document.createElement('div');
            item.className = 'flex flex-col items-center gap-2 rounded-xl border p-3 text-center';
            item.classList.add(isCurrent
                ? 'border-[#E31E24] bg-red-50'
                : (isComplete ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white'));

            const marker = document.createElement('span');
            marker.className = 'flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold';
            marker.classList.add(isCurrent
                ? 'bg-[#E31E24] text-white'
                : (isComplete ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'));
            marker.textContent = isComplete ? '✓' : String(index + 1);

            const label = document.createElement('span');
            label.className = 'text-xs font-semibold';
            label.textContent = step;
            item.append(marker, label);
            timeline.append(item);
        });

        completeForm.action = trigger.dataset.completeUrl;
        completeForm.classList.toggle('hidden', !(isFreshChicken && status === 'Ready'));
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeOrderStatus() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Order view modal
    const viewModal = document.getElementById('orderViewModal');
    const viewTriggers = [...document.querySelectorAll('[data-order-view]')];

    function openViewOrder(trigger) {
        document.getElementById('orderViewId').textContent = trigger.dataset.viewId;
        document.getElementById('orderViewDate').textContent = trigger.dataset.viewDate;
        document.getElementById('orderViewStatus').textContent = trigger.dataset.viewStatus;
        document.getElementById('orderViewType').textContent = trigger.dataset.viewType;
        document.getElementById('orderViewMethod').textContent = trigger.dataset.viewMethod;

        const total = parseFloat(trigger.dataset.viewTotal || 0);
        const paid = parseFloat(trigger.dataset.viewPaid || 0);
        const change = parseFloat(trigger.dataset.viewChange || 0);

        document.getElementById('orderViewTotal').textContent = '₱' + total.toFixed(2);
        document.getElementById('orderViewPaid').textContent = '₱' + paid.toFixed(2);
        document.getElementById('orderViewChange').textContent = '₱' + change.toFixed(2);

        const itemsContainer = document.getElementById('orderViewItems');
        const emptyState = document.getElementById('orderViewEmpty');

        try {
            const items = JSON.parse(trigger.dataset.viewItems || '[]');
            if (items.length === 0) {
                itemsContainer.classList.add('hidden');
                emptyState.classList.remove('hidden');
            } else {
                itemsContainer.classList.remove('hidden');
                emptyState.classList.add('hidden');
                itemsContainer.innerHTML = items.map(item => `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">${item.name}</td>
                        <td class="px-4 py-3 text-right text-slate-700">₱${Number(item.unit_price || 0).toFixed(2)}</td>
                        <td class="px-4 py-3 text-right text-slate-700">${item.qty}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">₱${Number(item.subtotal || 0).toFixed(2)}</td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            itemsContainer.classList.add('hidden');
            emptyState.classList.remove('hidden');
        }

        viewModal.classList.remove('hidden');
        viewModal.classList.add('flex');
    }

    function closeViewOrder() {
        viewModal.classList.add('hidden');
        viewModal.classList.remove('flex');
    }

    viewTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => openViewOrder(trigger));
    });

    document.querySelectorAll('[data-close-order-view]').forEach((button) => {
        button.addEventListener('click', closeViewOrder);
    });

    viewModal.addEventListener('click', (event) => {
        if (event.target === viewModal) {
            closeViewOrder();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !viewModal.classList.contains('hidden')) {
            closeViewOrder();
        }
    });

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', () => openOrderStatus(trigger));
    });
    document.querySelectorAll('[data-close-order-status]').forEach((button) => {
        button.addEventListener('click', closeOrderStatus);
    });
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeOrderStatus();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeOrderStatus();
        }
    });

    const autoOpenTrigger = triggers.find((trigger) => trigger.dataset.autoOpen === 'true');
    if (autoOpenTrigger) {
        openOrderStatus(autoOpenTrigger);
    }
})();
</script>
@endpush

@endsection
