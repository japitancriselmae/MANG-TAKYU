<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Mang Takyu</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mang-takyu-logo.png') }}">

    {{-- CDN Tailwind — works without any build step --}}
    <script src="{{ asset('js/tailwind.js') }}"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            black: '#0B0B0B',
                            red:   '#E31E24',
                            amber: '#F5A623',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    {{-- Inter font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen font-sans">

@php
    $authUser     = auth()->user();
    $authEmployee = $authUser?->employee;
    $authRole     = $authEmployee?->role?->role_name;

    // Admin is treated like Manager for navigation
    $isManagerLike = in_array($authRole, ['Admin', 'Manager']);

    $navGroups = [[
        'items' => [['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home']],
    ]];

    $ops = [];
    if ($isManagerLike || $authRole === 'Cashier') {
        $ops[] = ['route' => 'orders.index',  'label' => 'Orders',        'icon' => 'receipt'];
        $ops[] = ['route' => 'orders.create', 'label' => 'New Order',     'icon' => 'plus'];
    }
    if ($isManagerLike || $authRole === 'Kitchen Staff') {
        $ops[] = ['route' => 'kitchen.index', 'label' => 'Kitchen Queue', 'icon' => 'fire'];
    }
    if ($isManagerLike || $authRole === 'Cashier') {
        $ops[] = ['route' => 'payments.index','label' => 'Payments',      'icon' => 'cash'];
    }
    if ($ops) $navGroups[] = ['label' => 'Operations', 'items' => $ops];

    $team = [];
    if ($isManagerLike) {
        $team[] = ['route' => 'employees.index','label' => 'Employees',    'icon' => 'users'];
        $team[] = ['route' => 'users.index',    'label' => 'User Accounts','icon' => 'users'];
        $team[] = ['route' => 'roles.index',    'label' => 'Roles',        'icon' => 'users'];
        $team[] = ['route' => 'schedules.index','label' => 'Schedules',    'icon' => 'calendar'];
    }
    if ($isManagerLike || in_array($authRole, ['Cashier', 'Kitchen Staff'])) {
        $team[] = ['route' => 'attendances.index','label' => 'Attendance','icon' => 'clock'];
    }
    if ($team) $navGroups[] = ['label' => 'Team', 'items' => $team];

    $inv = [];
    if ($isManagerLike) {
        $inv[] = ['route' => 'products.index',            'label' => 'Products',        'icon' => 'box'];
        $inv[] = ['route' => 'ingredients.index',         'label' => 'Ingredients',     'icon' => 'leaf'];
        $inv[] = ['route' => 'inventories.index',         'label' => 'Inventory',       'icon' => 'archive'];
        $inv[] = ['route' => 'product-ingredients.index', 'label' => 'Recipes',         'icon' => 'book'];
        $inv[] = ['route' => 'stock-movements.index',     'label' => 'Stock Movements', 'icon' => 'arrows'];
        $inv[] = ['route' => 'stock-notifications.index', 'label' => 'Stock Alerts',    'icon' => 'bell'];
    }
    if ($inv) $navGroups[] = ['label' => 'Inventory', 'items' => $inv];

    $lowStock = collect(); $outOfStock = collect();
    if ($isManagerLike) {
        $lowStock   = \App\Models\Inventory::with('ingredient')
            ->whereColumn('quantity', '<=', 'minimum_stock')
            ->where('quantity', '>', 0)->orderBy('quantity')->get();
        $outOfStock = \App\Models\Inventory::with('ingredient')
            ->where('quantity', '<=', 0)->orderBy('quantity')->get();
    }
    $stockNotificationCount = $lowStock->count() + $outOfStock->count();
@endphp

<div class="flex min-h-screen">

    {{-- ============ SIDEBAR ============ --}}
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full lg:translate-x-0
               transition-transform duration-200 bg-brand-black text-white flex flex-col">

        <div class="h-20 flex items-center gap-3 px-5 border-b border-white/10">
            <img src="{{ asset('images/mang-takyu-logo.png') }}"
                alt="Mang Takyu"
                class="h-12 w-12 rounded-full object-cover bg-white ring-2 ring-[#E31E24]">
            <div class="leading-tight">
                <div class="font-extrabold tracking-wide text-white">MANG TAKYU</div>
                <div class="text-[10px] uppercase tracking-widest text-[#F5A623]">
                    Management System
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6">
            @foreach ($navGroups as $group)
                <div>
                    @if (!empty($group['label']))
                        <div class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-white/40">
                            {{ $group['label'] }}
                        </div>
                    @endif
                    <ul class="space-y-1">
                        @foreach ($group['items'] as $item)
                            @php $isActive = request()->routeIs($item['route']); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                                          {{ $isActive
                                                ? 'bg-brand-red text-white shadow-lg shadow-red-900/30'
                                                : 'text-white/70 hover:bg-white/5 hover:text-white' }}">
                                    <span class="w-5 h-5 shrink-0">
                                        @include('partials.icon', ['name' => $item['icon']])
                                    </span>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="p-3 border-t border-white/10">
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-9 h-9 rounded-full bg-brand-red text-white flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold truncate">{{ $authUser->name }}</div>
                    <div class="text-xs text-white/50 truncate">{{ $authRole ?? 'No Role' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg
                               bg-white/5 hover:bg-brand-red text-white/80 hover:text-white text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden"></div>

    {{-- ============ MAIN ============ --}}
    <div class="flex-1 flex flex-col lg:ml-64 min-w-0">

        <header class="sticky top-0 z-20 h-16 bg-white/90 backdrop-blur border-b border-slate-200 flex items-center gap-3 px-4 sm:px-6">
            <button id="sidebarToggle" type="button"
                    class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
                </svg>
            </button>

            <div class="flex-1 min-w-0">
                <h1 class="text-base sm:text-lg font-bold text-slate-800 truncate">
                    @yield('page-title', 'Dashboard')
                </h1>
                @hasSection('page-subtitle')
                    <p class="hidden sm:block text-xs text-slate-500 truncate">@yield('page-subtitle')</p>
                @endif
            </div>

            @if ($isManagerLike)
                <div class="relative">
                    <button id="notificationButton" type="button"
                            class="relative p-2 rounded-lg hover:bg-slate-100 text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                        </svg>
                        @if ($stockNotificationCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full
                                         bg-brand-red text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white">
                                {{ $stockNotificationCount }}
                            </span>
                        @endif
                    </button>

                    <div id="notificationDropdown"
                         class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl
                                border border-slate-200 overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800 text-sm">Stock Notifications</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-brand-red font-bold">
                                {{ $stockNotificationCount }}
                            </span>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @if ($stockNotificationCount === 0)
                                <div class="p-8 text-center text-slate-500 text-sm">
                                    <div class="text-3xl mb-2">✓</div>
                                    All inventory levels are normal.
                                </div>
                            @else
                                @foreach ($outOfStock as $inv)
                                    <a href="{{ route('stock-notifications.index') }}"
                                       class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 border-b border-slate-100">
                                        <div class="w-8 h-8 rounded-full bg-red-100 text-brand-red flex items-center justify-center font-bold text-sm shrink-0">!</div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-slate-800 truncate">
                                                {{ $inv->ingredient?->ingredient_name ?? 'Unknown' }}
                                            </div>
                                            <div class="text-xs text-slate-500">Out of stock</div>
                                        </div>
                                    </a>
                                @endforeach
                                @foreach ($lowStock as $inv)
                                    <a href="{{ route('stock-notifications.index') }}"
                                       class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 border-b border-slate-100">
                                        <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-sm shrink-0">!</div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-slate-800 truncate">
                                                {{ $inv->ingredient?->ingredient_name ?? 'Unknown' }}
                                            </div>
                                            <div class="text-xs text-slate-500">Low stock</div>
                                        </div>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                        @if ($stockNotificationCount > 0)
                            <a href="{{ route('stock-notifications.index') }}"
                               class="block text-center px-4 py-3 text-sm font-semibold text-brand-red hover:bg-slate-50 border-t border-slate-100">
                                View all notifications →
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="hidden sm:flex items-center gap-3 pl-3 border-l border-slate-200">
                <div class="text-right leading-tight">
                    <div class="text-sm font-semibold text-slate-800">{{ $authUser->name }}</div>
                    <div class="text-xs text-slate-500">{{ $authRole ?? 'No Role' }}</div>
                </div>
                <div class="w-9 h-9 rounded-full bg-brand-black text-brand-amber flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6 lg:p-8">

            @if (session('success'))
                <div class="mb-5 flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                    <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm">{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800">
                    <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <div class="text-sm">
                        <p class="font-semibold mb-1">Please fix the following:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
(function () {
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    toggle?.addEventListener('click', () => {
        const hidden = sidebar.classList.contains('-translate-x-full');
        sidebar.classList.toggle('-translate-x-full', !hidden);
        overlay.classList.toggle('hidden', !hidden);
    });
    overlay?.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });

    const notifBtn  = document.getElementById('notificationButton');
    const notifDrop = document.getElementById('notificationDropdown');

    notifBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        notifDrop.classList.toggle('hidden');
    });
    notifDrop?.addEventListener('click', (e) => e.stopPropagation());
    document.addEventListener('click', () => notifDrop?.classList.add('hidden'));
})();
</script>

@stack('scripts')
<style>
    /* ============================================
       BLACK SCROLLBAR FOR SIDEBAR
    ============================================ */

    /* Chrome / Edge / Safari */
    #sidebar ::-webkit-scrollbar {
        width: 8px;
        background: #0B0B0B;
    }
    #sidebar ::-webkit-scrollbar-track {
        background: #0B0B0B;
    }
    #sidebar ::-webkit-scrollbar-thumb {
        background: #2a2a2a;
        border-radius: 4px;
    }
    #sidebar ::-webkit-scrollbar-thumb:hover {
        background: #3a3a3a;
    }
    #sidebar ::-webkit-scrollbar-corner {
        background: #0B0B0B;
    }

    /* Firefox */
    #sidebar * {
        scrollbar-width: thin;
        scrollbar-color: #2a2a2a #0B0B0B;
    }

    /* Force sidebar + inner nav to stay black */
    #sidebar,
    #sidebar nav {
        background-color: #0B0B0B !important;
    }

    /* Kill the white body background that peeks through while scrolling */
    html,
    body {
        background-color: #0B0B0B;
    }
</style>

<style>
    /* ============================================
       BRANDED WATERMARK BACKGROUND
    ============================================ */

    main {
        position: relative;
        background-color: #f8fafc;
    }

    /* Desktop / Web */
    main::before {
        content: "";
        position: fixed;
        inset: 0;
        left: 16rem;
        background-image: url("{{ asset('images/mang-takyu-logo.png') }}");
        background-repeat: no-repeat;
        background-position: center center;
        background-size: 900 px;
        opacity: 0.18;
        pointer-events: none;
        z-index: 0;
    }

    main > * {
        position: relative;
        z-index: 1;
    }

    /* Tablet */
    @media (max-width: 1023px) {
        main::before {
            left: 0;
            background-size: 500px;
            opacity: 0.15;
        }
    }

    /* Phone */
    @media (max-width: 640px) {
        main::before {
            left: 0;
            background-size: 90vw;
            opacity: 0.12;
        }
    }
</style>
</body>
</html>