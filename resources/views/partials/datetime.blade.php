<div class="flex items-center gap-2 bg-white/50 backdrop-blur border border-slate-200 rounded-xl px-3 py-1.5 text-sm">
    <div class="flex h-6 w-6 items-center justify-center rounded bg-[#E31E24]/10 text-[#E31E24]" aria-hidden="true">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <rect x="3.75" y="5.25" width="16.5" height="15" rx="2"/>
            <path stroke-linecap="round" d="M7.5 3.75v3M16.5 3.75v3M3.75 9.75h16.5"/>
        </svg>
    </div>
    <div class="text-left leading-none">
        <p class="text-slate-700 font-semibold" id="datetime-date">{{ now()->format('l, F j, Y') }}</p>
        <p class="text-slate-500 text-xs tabular-nums" id="datetime-time">{{ now()->format('h:i:s A') }}</p>
    </div>
</div>
