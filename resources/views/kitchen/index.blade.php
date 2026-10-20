@extends('layouts.app')

@section('title', 'Kitchen Queue')
@section('page-title', 'Kitchen Queue')
@section('page-subtitle', 'Track chicken cooking batches.')

@section('content')

<section class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Cooking Batches</h2>
        <p class="mt-1 text-sm text-slate-500">
            Track kitchen-wide chicken batches. Batches do not change orders or POS availability.
        </p>
    </div>

    <form action="{{ route('kitchen.batches.reset') }}" method="POST"
          class="mt-4 inline-block">
        @csrf @method('DELETE')
        <button type="submit"
                onclick="return confirm('Reset today\'s kitchen queue? This will remove all batches started today.');"
                class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
            Reset Today's Queue
        </button>
    </form>

    @error('batch')
        <p class="mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
    @enderror

    <form action="{{ route('kitchen.batches.store') }}" method="POST"
          class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @csrf
        <input type="hidden" name="food_type" value="chicken">
        <label>
            <span class="mb-1 block text-xs font-semibold text-slate-600">Chicken batch quantity (pieces)</span>
            <input type="number" name="quantity" required min="1" max="1000000"
                   value="{{ old('quantity') }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
        </label>
        <label>
            <span class="mb-1 block text-xs font-semibold text-slate-600">Timer (minutes)</span>
            <input type="number" name="duration_minutes" required min="1" max="1440"
                   value="{{ old('duration_minutes') }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#E31E24] focus:outline-none focus:ring-2 focus:ring-[#E31E24]/20">
        </label>
        <div class="flex items-end">
            <button type="submit"
                    class="w-full rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                Start Batch Timer
            </button>
        </div>
    </form>

    @if ($batches->isNotEmpty())
        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($batches as $batch)
                @php
                    $batchTimerFinished = $batch->started_at
                        ->copy()
                        ->addMinutes($batch->duration_minutes)
                        ->isPast();
                @endphp
                <article class="rounded-xl border border-slate-200 p-4" data-batch-card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-bold capitalize text-slate-800">{{ $batch->quantity }} pcs chicken</h3>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $batch->duration_minutes }} min · Started {{ $batch->started_at->format('h:i A') }}
                            </p>
                        </div>
                        <span class="text-xs text-slate-500">
                            {{ $batch->employee?->first_name }} {{ $batch->employee?->last_name }}
                        </span>
                    </div>
                    <div class="cooking-countdown mt-3 font-mono text-2xl font-bold tabular-nums text-amber-700"
                         role="timer"
                         data-batch-status="{{ $batch->status }}"
                         data-started-at="{{ $batch->started_at->toIso8601String() }}"
                         data-duration-minutes="{{ $batch->duration_minutes }}">--:--</div>
                    @if ($batch->status === 'ready')
                        <p class="mt-2 text-sm font-semibold text-emerald-700">
                            Ready for service · {{ $batch->ready_at?->format('h:i A') }}
                        </p>
                    @else
                        <form action="{{ route('kitchen.batches.ready', $batch) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit"
                                    @if (! $batchTimerFinished) hidden @endif
                                    class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Confirm Batch Ready
                            </button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    @else
        <p class="mt-5 rounded-xl bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">
            No cooking batches have been started.
        </p>
    @endif
</section>

@push('scripts')
<script>
function updateCookingCountdowns() {
    document.querySelectorAll('.cooking-countdown').forEach((element) => {
        const startedAt = new Date(element.dataset.startedAt).getTime();
        const duration = Number(element.dataset.durationMinutes) * 60 * 1000;
        const secondsLeft = Math.max(0, Math.ceil((startedAt + duration - Date.now()) / 1000));

        if (secondsLeft === 0) {
            element.textContent = 'Timer finished';
            element.classList.remove('text-amber-700');
            element.classList.add('text-emerald-700');
            if (element.dataset.batchStatus === 'cooking') {
                const readyButton = element.closest('[data-batch-card]')
                    ?.querySelector('button[type="submit"]');
                readyButton?.removeAttribute('hidden');
            }
            return;
        }

        const hours = Math.floor(secondsLeft / 3600);
        const minutes = Math.floor((secondsLeft % 3600) / 60);
        const seconds = secondsLeft % 60;
        const clock = hours > 0
            ? [hours, minutes, seconds].map((part) => String(part).padStart(2, '0')).join(':')
            : [minutes, seconds].map((part) => String(part).padStart(2, '0')).join(':');

        element.textContent = clock;
    });
}
updateCookingCountdowns();
setInterval(updateCookingCountdowns, 1000);
</script>
@endpush

@endsection
