@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back — here\'s what\'s happening today.')

@section('content')

@php
    use Illuminate\Support\Carbon;

    $user     = auth()->user();
    $employee = $user->employee;
    $role     = $employee?->role?->role_name;
    $today    = Carbon::today();

    $stats = [];

    if ($role === 'Manager') {
        $stats[] = [
            'label' => 'Total Employees',
            'value' => \App\Models\Employee::count(),
            'sub'   => \App\Models\Employee::where('status','Active')->count() . ' active',
            'color' => 'black', 'icon' => 'users',
        ];
        $stats[] = [
            'label' => 'Orders Today',
            'value' => \App\Models\Order::whereDate('order_date', $today)->count(),
            'sub'   => '₱' . number_format((float) \App\Models\Order::whereDate('order_date',$today)->sum('total_amount'), 2),
            'color' => 'red', 'icon' => 'receipt',
        ];
        $stats[] = [
            'label' => 'Pending Orders',
            'value' => \App\Models\Order::whereIn('status', ['Pending','Confirmed','Preparing'])->count(),
            'sub'   => 'In the pipeline',
            'color' => 'amber', 'icon' => 'fire',
        ];
        $stats[] = [
            'label' => 'Stock Alerts',
            'value' => \App\Models\Inventory::whereColumn('quantity','<=','minimum_stock')->count(),
            'sub'   => 'Low or out of stock',
            'color' => 'red', 'icon' => 'bell',
        ];
    } else {
        $todaySchedule = \App\Models\Schedule::where('employee_id', $employee?->employee_id)
            ->whereDate('schedule_date', $today)->where('status','Active')
            ->orderBy('start_time')->first();

        $todayAttendance = \App\Models\Attendance::where('employee_id', $employee?->employee_id)
            ->whereDate('date', $today)->first();

        $stats[] = [
            'label' => 'Today\'s Shift',
            'value' => $todaySchedule
                ? Carbon::parse($todaySchedule->start_time)->format('h:i A') . ' – ' . Carbon::parse($todaySchedule->end_time)->format('h:i A')
                : 'No shift',
            'sub'   => $todaySchedule ? 'Scheduled' : 'You are off today',
            'color' => 'black', 'icon' => 'calendar',
        ];
        $stats[] = [
            'label' => 'Clock In',
            'value' => $todayAttendance?->time_in ? Carbon::parse($todayAttendance->time_in)->format('h:i A') : '—',
            'sub'   => $todayAttendance ? 'Recorded today' : 'Not yet recorded',
            'color' => 'red', 'icon' => 'clock',
        ];
        $stats[] = [
            'label' => 'Clock Out',
            'value' => $todayAttendance?->time_out ? Carbon::parse($todayAttendance->time_out)->format('h:i A') : '—',
            'sub'   => $todayAttendance?->time_out ? 'Recorded today' : 'Pending',
            'color' => 'amber', 'icon' => 'clock',
        ];
        $stats[] = [
            'label' => 'Late Minutes',
            'value' => ($todayAttendance?->late_minutes ?? 0) . ' min',
            'sub'   => 'Today',
            'color' => 'red', 'icon' => 'clock',
        ];
    }

    $actions = [];
    if ($role === 'Manager') {
        $actions[] = ['title' => 'Employees', 'desc' => 'Manage staff and roles', 'route' => 'employees.index', 'color' => 'black', 'icon' => 'users'];
    }
    if (in_array($role, ['Manager','Cashier'])) {
        $actions[] = ['title' => 'New Order', 'desc' => 'Create a customer order', 'route' => 'orders.create', 'color' => 'red', 'icon' => 'plus'];
        $actions[] = ['title' => 'Orders',    'desc' => 'View and manage orders',  'route' => 'orders.index',  'color' => 'amber', 'icon' => 'receipt'];
    }
    if (in_array($role, ['Manager','Kitchen Staff'])) {
        $actions[] = ['title' => 'Kitchen',   'desc' => 'Prepare & ready orders',  'route' => 'kitchen.index', 'color' => 'red', 'icon' => 'fire'];
    }
    if (in_array($role, ['Manager','Cashier'])) {
        $actions[] = ['title' => 'Payments',  'desc' => 'Record and view payments','route' => 'payments.index','color' => 'amber', 'icon' => 'cash'];
    }
    if (in_array($role, ['Manager','Cashier','Kitchen Staff'])) {
        $actions[] = ['title' => 'Attendance','desc' => 'Clock in / clock out',    'route' => 'attendances.index', 'color' => 'black', 'icon' => 'clock'];
    }
    if ($role === 'Manager') {
        $actions[] = ['title' => 'Products',  'desc' => 'Manage product catalog',  'route' => 'products.index', 'color' => 'red', 'icon' => 'box'];
        $actions[] = ['title' => 'Inventory', 'desc' => 'Track ingredient stock',  'route' => 'inventories.index','color' => 'amber', 'icon' => 'archive'];
        $actions[] = ['title' => 'Recipes',   'desc' => 'Product ingredient recipes','route' => 'product-ingredients.index', 'color' => 'black', 'icon' => 'book'];
        $actions[] = ['title' => 'Schedules', 'desc' => 'Assign employee shifts',  'route' => 'schedules.index', 'color' => 'red', 'icon' => 'calendar'];
    }
@endphp

{{-- HERO --}}
<div class="rounded-2xl bg-gradient-to-br from-[#0B0B0B] via-[#1a1a1a] to-[#0B0B0B] text-white p-6 sm:p-8 mb-6 shadow-xl relative overflow-hidden border border-white/5">
    <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-[#E31E24]/30 blur-3xl"></div>
    <div class="absolute -bottom-16 -left-16 w-64 h-64 rounded-full bg-[#F5A623]/20 blur-3xl"></div>

    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-[#F5A623] text-xs uppercase tracking-widest font-semibold mb-1">
                {{ now()->format('l, F j, Y') }}
            </p>
            <h2 class="text-2xl sm:text-3xl font-bold">
                Welcome back, {{ explode(' ', $user->name)[0] }}! 🍗
            </h2>
            <p class="text-white/60 mt-2 text-sm max-w-lg">
                Here's a quick overview of your operations today.
            </p>
        </div>
        <span class="inline-flex items-center gap-2 self-start px-4 py-2 rounded-full bg-[#E31E24] text-sm font-semibold shadow-lg shadow-red-900/40">
            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
            {{ $role ?? 'No Role' }}
        </span>
    </div>
</div>

{{-- STATS --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    @foreach ($stats as $s)
        @php
            $map = [
                'black' => 'from-[#0B0B0B] to-[#2a2a2a]',
                'red'   => 'from-[#E31E24] to-[#b8171c]',
                'amber' => 'from-[#F5A623] to-[#e0920e]',
            ];
            $grad = $map[$s['color']] ?? $map['black'];
        @endphp
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md transition">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $grad }} text-white flex items-center justify-center shadow-sm mb-3">
                <span class="w-5 h-5">
                    @include('partials.icon', ['name' => $s['icon']])
                </span>
            </div>
            <div class="text-2xl font-bold text-slate-800 truncate">{{ $s['value'] }}</div>
            <div class="text-sm font-medium text-slate-600 mt-0.5">{{ $s['label'] }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $s['sub'] }}</div>
        </div>
    @endforeach
</div>

{{-- QUICK ACTIONS --}}
<h3 class="text-lg font-bold text-slate-800 mb-4">Quick Actions</h3>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach ($actions as $a)
        @php
            $map = [
                'black' => 'bg-slate-100 text-[#0B0B0B] group-hover:bg-slate-200',
                'red'   => 'bg-red-50 text-[#E31E24] group-hover:bg-red-100',
                'amber' => 'bg-amber-50 text-[#F5A623] group-hover:bg-amber-100',
            ];
            $tint = $map[$a['color']] ?? $map['black'];
        @endphp
        <a href="{{ route($a['route']) }}"
           class="group bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md
                  hover:-translate-y-0.5 hover:border-[#E31E24]/30 transition flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl {{ $tint }} flex items-center justify-center shrink-0 transition">
                <span class="w-5 h-5">
                    @include('partials.icon', ['name' => $a['icon']])
                </span>
            </div>
            <div class="min-w-0">
                <div class="font-semibold text-slate-800 group-hover:text-[#E31E24] transition">
                    {{ $a['title'] }}
                </div>
                <div class="text-xs text-slate-500 mt-0.5">{{ $a['desc'] }}</div>
            </div>
        </a>
    @endforeach
</div>

@endsection