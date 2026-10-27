@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

@php
    $cards = [
        [
            'label' => 'Orders Today',
            'value' => number_format($totalOrders),
            'description' => 'Orders created today',
            'icon' => 'receipt',
            'color' => 'red',
        ],
        [
            'label' => 'Sales Today',
            'value' => '₱' . number_format($salesToday, 2),
            'description' => 'Payments received, less change',
            'icon' => 'sales',
            'color' => 'emerald',
        ],
    ];

    $iconColors = [
        'red' => 'bg-red-50 text-[#E31E24]',
        'emerald' => 'bg-emerald-50 text-emerald-600',
    ];
@endphp

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Today's Overview</h2>
            <p class="mt-1 text-sm text-slate-500">Orders and payment summary for today.</p>
        </div>

        <div class="flex items-center gap-3 self-start rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:self-auto">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-white" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <rect x="3.75" y="5.25" width="16.5" height="15" rx="2"/>
                    <path stroke-linecap="round" d="M7.5 3.75v3M16.5 3.75v3M3.75 9.75h16.5"/>
                </svg>
            </span>
            <div class="leading-tight">
                <time id="dashboard-date" class="block text-sm font-semibold text-slate-800">
                    {{ now()->format('l, F j, Y') }}
                </time>
                <time id="dashboard-time" class="mt-1 block text-xs font-medium tabular-nums text-slate-500">
                    {{ now()->format('h:i:s A') }}
                </time>
            </div>
        </div>
    </div>

    <section aria-label="Today's business metrics" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($cards as $card)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                        <p class="mt-3 truncate text-3xl font-bold tracking-tight text-slate-900">
                            {{ $card['value'] }}
                        </p>
                        <p class="mt-2 text-xs text-slate-400">{{ $card['description'] }}</p>
                    </div>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $iconColors[$card['color']] }}" aria-hidden="true">
                        @if ($card['icon'] === 'receipt')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h9A1.5 1.5 0 0118 5.25v15l-3-1.875L12 20.25l-3-1.875L6 20.25v-15a1.5 1.5 0 011.5-1.5zM9 8.25h6M9 12h6"/>
                            </svg>
                        @elseif ($card['icon'] === 'sales')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75v16.5m4.5-12.75c0-1.243-2.015-2.25-4.5-2.25s-4.5 1.007-4.5 2.25S9.515 9.75 12 9.75s4.5 1.007 4.5 2.25-2.015 2.25-4.5 2.25-4.5 1.007-4.5 2.25S9.515 18.75 12 18.75s4.5-1.007 4.5-2.25"/>
                            </svg>
                        @elseif ($card['icon'] === 'completed')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.75l-11.25 11.25L3.75 12.75"/>
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <rect x="3.75" y="5.25" width="16.5" height="13.5" rx="2"/>
                                <path stroke-linecap="round" d="M7.5 9h9M7.5 12h6"/>
                            </svg>
                        @endif
                    </span>
                </div>
            </article>
        @endforeach
    </section>

    @if ($canViewSalesComparison)
    <section aria-labelledby="sales-comparison-title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 id="sales-comparison-title" class="text-lg font-bold text-slate-900">Sales Comparison</h2>
                <p class="mt-1 text-sm text-slate-500">Compare a selected period with the previous comparable period.</p>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs font-medium text-slate-600">
                <span class="inline-flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-sm bg-[#E31E24]" aria-hidden="true"></span>
                    {{ $currentRangeLabel }}
                </span>
                <span class="inline-flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-sm bg-slate-300" aria-hidden="true"></span>
                    Previous period ({{ $previousRangeLabel }})
                </span>
            </div>
        </div>

        <form method="GET" action="{{ url('/') }}" class="mt-5 grid grid-cols-1 gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @if ($errors->any())
                <p role="alert" class="text-sm font-medium text-red-700 sm:col-span-2 xl:col-span-3">{{ $errors->first() }}</p>
            @endif
            <label class="text-xs font-semibold text-slate-600">
                From
                <input type="date" name="start_date" value="{{ $startDate }}" max="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            </label>
            <label class="text-xs font-semibold text-slate-600">
                To
                <input type="date" name="end_date" value="{{ $endDate }}" max="{{ now()->toDateString() }}" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500">
            </label>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-[#E31E24] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Apply filters
                </button>
            </div>
        </form>

        <div class="mt-5 grid grid-cols-2 gap-3 sm:max-w-md">
            <div class="rounded-xl bg-red-50 px-4 py-3">
                <p class="text-xs font-medium text-slate-500">Selected period</p>
                <p class="mt-1 text-lg font-bold text-slate-900">₱{{ number_format($currentSalesTotal, 2) }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-xs font-medium text-slate-500">Previous period</p>
                <p class="mt-1 text-lg font-bold text-slate-900">₱{{ number_format($previousSalesTotal, 2) }}</p>
            </div>
        </div>

            @php
                $chartWidth = max(720, count($salesComparison) * 110 + 100);
                $chartLeft = 76;
                $chartRight = $chartWidth - 24;
                $chartTop = 20;
                $chartBottom = 228;
                $chartHeight = $chartBottom - $chartTop;
                $currentLinePoints = [];
                $previousLinePoints = [];

                foreach ($salesComparison as $index => $bucket) {
                    $x = count($salesComparison) > 1
                        ? $chartLeft + $index * (($chartRight - $chartLeft) / (count($salesComparison) - 1))
                        : ($chartLeft + $chartRight) / 2;
                    $currentY = $chartBottom - ($bucket['current_sales'] / $salesChartMax) * $chartHeight;
                    $previousY = $chartBottom - ($bucket['previous_sales'] / $salesChartMax) * $chartHeight;
                    $currentLinePoints[] = ['x' => $x, 'y' => $currentY];
                    $previousLinePoints[] = ['x' => $x, 'y' => $previousY];
                }
            @endphp

            <div class="mt-6 overflow-x-auto">
                <svg
                    role="img"
                    aria-label="Line graph comparing sales for the selected and previous periods"
                    viewBox="0 0 {{ $chartWidth }} 280"
                    class="h-72 min-w-full"
                    style="min-width: {{ $chartWidth }}px"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    @foreach (range(0, 4) as $gridLine)
                        @php
                            $gridY = $chartTop + ($gridLine / 4) * $chartHeight;
                            $gridValue = $salesChartMax * (1 - $gridLine / 4);
                        @endphp
                        <line x1="{{ $chartLeft }}" y1="{{ $gridY }}" x2="{{ $chartRight }}" y2="{{ $gridY }}" stroke="#e2e8f0" stroke-dasharray="4 4" />
                        <text x="{{ $chartLeft - 10 }}" y="{{ $gridY + 4 }}" text-anchor="end" fill="#64748b" font-size="11">₱{{ number_format($gridValue, 0) }}</text>
                    @endforeach

                    <polyline
                        points="{{ collect($currentLinePoints)->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}"
                        fill="none"
                        stroke="#E31E24"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                    />
                    <polyline
                        points="{{ collect($previousLinePoints)->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}"
                        fill="none"
                        stroke="#94a3b8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                    />

                    @foreach ($salesComparison as $index => $bucket)
                        @php
                            $currentPoint = $currentLinePoints[$index];
                            $previousPoint = $previousLinePoints[$index];
                        @endphp
                        <circle cx="{{ $currentPoint['x'] }}" cy="{{ $currentPoint['y'] }}" r="5" fill="#E31E24">
                            <title>{{ $currentRangeLabel }} — {{ $bucket['current_label'] }}: ₱{{ number_format($bucket['current_sales'], 2) }}</title>
                        </circle>
                        <circle cx="{{ $previousPoint['x'] }}" cy="{{ $previousPoint['y'] }}" r="5" fill="#94a3b8">
                            <title>Previous period — {{ $bucket['previous_label'] }}: ₱{{ number_format($bucket['previous_sales'], 2) }}</title>
                        </circle>
                        <text x="{{ $currentPoint['x'] }}" y="258" text-anchor="middle" fill="#64748b" font-size="11">{{ $bucket['label'] }}</text>
                    @endforeach
                </svg>
            </div>
        </div>
    </section>
    @endif
</div>

@push('scripts')
<script>
    (() => {
        const dateElement = document.getElementById('dashboard-date');
        const timeElement = document.getElementById('dashboard-time');

        if (!dateElement || !timeElement) {
            return;
        }

        const dateFormatter = new Intl.DateTimeFormat(undefined, {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        });
        const timeFormatter = new Intl.DateTimeFormat(undefined, {
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
        });

        const updateClock = () => {
            const now = new Date();
            dateElement.textContent = dateFormatter.format(now);
            timeElement.textContent = timeFormatter.format(now);
            timeElement.dateTime = now.toISOString();
        };

        updateClock();
        window.setInterval(updateClock, 1000);
    })();
</script>
@endpush

@endsection
