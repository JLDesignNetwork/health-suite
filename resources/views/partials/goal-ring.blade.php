@php
    $r         = 40;
    $stroke    = 8;
    $circ      = 2 * M_PI * $r;
    $filled    = $circ * (min(100, max(0, $pct)) / 100);
    $remaining = $circ - $filled;
@endphp
<div class="flex flex-col items-center gap-2">
    <svg width="100" height="100" viewBox="0 0 100 100" class="-rotate-90">
        <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="#e5e7eb" stroke-width="{{ $stroke }}" />
        <circle cx="50" cy="50" r="{{ $r }}" fill="none"
            stroke="{{ $color }}" stroke-width="{{ $stroke }}"
            stroke-linecap="round"
            stroke-dasharray="{{ $filled }} {{ $remaining }}"
            stroke-dashoffset="0" />
    </svg>
    <div class="text-center -mt-1">
        <p class="text-sm font-semibold text-gray-800">{{ $value }}</p>
        <p class="text-xs text-gray-400">{{ $goal }}</p>
        <p class="text-xs font-medium mt-0.5" style="color: {{ $color }}">{{ $label }}</p>
    </div>
</div>
