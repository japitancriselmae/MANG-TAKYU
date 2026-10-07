@extends('layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')
@section('page-subtitle', 'Manage customer orders and order status.')

@section('content')

@php
    $user = auth()->user();
    $role = $user->employee?->role?->role_name;

    $statusStyles = [
        'Pending'   => ['bg' => 'bg-amber-100',   'text' => 'text-amber-700',   'dot' => 'bg-amber-500'],
        'Confirmed' => ['bg' => 'bg-blue-100',    'text' => 'text-blue-700',    'dot' => 'bg-blue-500'],
        'Preparing' => ['bg' => 'bg-purple-100',  'text' => 'text-purple-700',  'dot' => 'bg-purple-500'],
        'Ready'     => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
        'Paid'      => ['bg' => 'bg-teal-100',    'text' => 'text-teal-700',    'dot' => 'bg-teal-500'],
        'Completed' => ['bg' => 'bg-slate-200',   'text' => 'text-slate-700',   'dot' => 'bg-slate-500'],
    ];

    $pendingCount = $orders->where('status', 'Pending')->count();
    $activeCount  = $orders->whereIn('status', ['Confirmed', 'Preparing'])->count();
    $readyCount   = $orders->where('status', 'Ready')->count();
@endphp

{{-- KPI CARDS --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Total Orders</div>
        <div class="text-3xl font-bold text-slate-800">{{ $orders->count() }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Pending</div>
        <div class="text-3xl font-bold text-amber-600">{{ $pendingCount }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">In Kitchen</div>
        <div class="text-3xl font-bold text-purple-600">{{ $activeCount }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm">
        <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1">Ready to Pay</div>
        <div class="text-3xl font-bold text-emerald-600">{{ $readyCount }}</div>
    </div>
</div>

{{-- ACTIONS BAR --}}
<div class="flex items-center justify-between mb-5">
    <h3 class="text-lg font-bold text-slate-800">Order List</h3>
    @if (in_array($role, ['Manager', 'Cashier']))
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
                        <th class="px-6 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        @php $style = $statusStyles[$order->status] ?? $statusStyles['Pending']; @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800">#{{ $order->order_id }}</span>
                                    @if ($order->is_fresh_chicken)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-[#F5A623]/20 text-[#b5790b] text-[10px] font-bold">
                                            🍗 FRESH
                                        </span>
                                    @endif
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
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $style['bg'] }} {{ $style['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }}"></span>
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                        View
                                    </a>
                                    @if ($order->status === 'Pending')
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-20 text-center">
            <div class="text-6xl mb-4">🧾</div>
            <h3 class="text-xl font-bold text-slate-700">No orders yet</h3>
            <p class="text-sm text-slate-500 mt-2 mb-6">Start by creating your first customer order.</p>
            @if (in_array($role, ['Manager', 'Cashier']))
                <a href="{{ route('orders.create') }}"
                   class="inline-block px-6 py-3 rounded-xl bg-[#E31E24] hover:bg-red-700 text-white font-semibold shadow-lg shadow-red-500/20 transition">
                    Create First Order
                </a>
            @endif
        </div>
    @endif
</div>

@endsection