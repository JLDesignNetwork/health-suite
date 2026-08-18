<?php

use App\Enums\Gender;
use App\Models\HealthRecord;
use App\Models\Meal;
use App\Services\HealthService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class extends Component {
    public string $date;

    public function mount(): void
    {
        $this->date = today()->toDateString();
    }

    #[On('dashboard-refresh')]
    public function refresh(): void {} // triggers with() re-evaluation

    public function with(HealthService $svc): array
    {
        $profile = auth()->user()->profile;
        $today   = HealthRecord::whereDate('date', $this->date)->first();
        $meals   = Meal::whereDate('date', $this->date)->get();

        // --- Today stats ---
        $todayCalories = $meals->sum('calories');
        $todayWater    = $today?->water_intake_l ?? 0;
        $todayExercise = $today?->exercise_minutes ?? 0;

        $bmi = $bfp = $pulseDeviation = $bpVariance = null;

        if ($today && $profile) {
            if ($today->weight && $profile->height_cm) {
                $bmi = round($svc->bmi((float) $today->weight, (float) $profile->height_cm), 1);
            }
            if ($today->waist && $today->neck && $profile->height_cm) {
                try {
                    $bfp = round($svc->bodyFatPercent(
                        $profile->gender,
                        (float) $today->waist,
                        (float) $today->neck,
                        (float) $profile->height_cm,
                        $today->hip ? (float) $today->hip : null,
                    ), 1);
                } catch (\ValueError) {}
            }
            if ($today->pulse && $profile->baseline_pulse) {
                $pulseDeviation = round($svc->pulseDeviation($today->pulse, $profile->baseline_pulse), 1);
            }
            if ($today->systolic && $today->diastolic && $profile->baseline_systolic && $profile->baseline_diastolic) {
                $bpVariance = $svc->bloodPressureVariance(
                    $today->systolic, $today->diastolic,
                    $profile->baseline_systolic, $profile->baseline_diastolic,
                );
            }
        }

        // --- Goal ring progress (0–100 clamped) ---
        $calorieGoal   = $profile?->daily_calorie_goal;
        $waterGoal     = $profile?->daily_water_goal;
        $exerciseGoal  = $profile?->weekly_exercise_goal
            ? round($profile->weekly_exercise_goal / 7)
            : null;

        $caloriePct  = $calorieGoal  ? min(100, round($todayCalories / $calorieGoal * 100))  : null;
        $waterPct    = $waterGoal    ? min(100, round((float)$todayWater / (float)$waterGoal * 100)) : null;
        $exercisePct = $exerciseGoal ? min(100, round($todayExercise / $exerciseGoal * 100))  : null;

        // --- Trend chart data (last 30 records) ---
        $records = HealthRecord::whereNotNull('weight')
            ->orderBy('date')
            ->take(90)
            ->get(['date', 'weight', 'waist', 'neck', 'hip', 'systolic', 'diastolic', 'pulse']);

        $chartLabels = $records->map(fn ($r) => $r->date->format('M j'))->values()->toArray();

        $weightData    = $records->map(fn ($r) => $r->weight ? (float) $r->weight : null)->values()->toArray();
        $systolicData  = $records->map(fn ($r) => $r->systolic)->values()->toArray();
        $diastolicData = $records->map(fn ($r) => $r->diastolic)->values()->toArray();
        $pulseData     = $records->map(fn ($r) => $r->pulse)->values()->toArray();

        $bmiData = $records->map(function ($r) use ($svc, $profile) {
            if (! $r->weight || ! $profile?->height_cm) return null;
            return round($svc->bmi((float) $r->weight, (float) $profile->height_cm), 1);
        })->values()->toArray();

        $bfpData = $records->map(function ($r) use ($svc, $profile) {
            if (! $r->waist || ! $r->neck || ! $profile?->height_cm) return null;
            try {
                return round($svc->bodyFatPercent(
                    $profile->gender,
                    (float) $r->waist,
                    (float) $r->neck,
                    (float) $profile->height_cm,
                    $r->hip ? (float) $r->hip : null,
                ), 1);
            } catch (\ValueError) { return null; }
        })->values()->toArray();

        return compact(
            'profile', 'today', 'todayCalories', 'todayWater', 'todayExercise',
            'bmi', 'bfp', 'pulseDeviation', 'bpVariance',
            'caloriePct', 'waterPct', 'exercisePct',
            'calorieGoal', 'waterGoal', 'exerciseGoal',
            'chartLabels', 'weightData', 'bmiData', 'bfpData',
            'systolicData', 'diastolicData', 'pulseData',
        );
    }
}; ?>

<div class="space-y-6">

    {{-- Header + date picker --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
            Welcome, {{ auth()->user()->name }}
        </h1>
        <input type="date" wire:model.live="date"
            class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    {{-- Today summary card --}}
    @if ($today)
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-4">Today's Stats</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @if ($today->weight)
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ $today->weight }}<span class="text-sm font-normal text-gray-400 ml-1">kg</span></p>
                        <p class="text-xs text-gray-400 mt-0.5">Weight</p>
                        @if ($profile?->target_weight)
                            @php $diff = round((float)$today->weight - (float)$profile->target_weight, 1); @endphp
                            <p @class(['text-xs mt-0.5', 'text-green-600' => $diff <= 0, 'text-amber-500' => $diff > 0])>
                                {{ $diff > 0 ? '+' : '' }}{{ $diff }} from goal
                            </p>
                        @endif
                    </div>
                @endif
                @if ($bmi !== null)
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ $bmi }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">BMI</p>
                    </div>
                @endif
                @if ($bfp !== null)
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ $bfp }}<span class="text-sm font-normal text-gray-400 ml-0.5">%</span></p>
                        <p class="text-xs text-gray-400 mt-0.5">Body Fat</p>
                    </div>
                @endif
                @if ($today->pulse)
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900">{{ $today->pulse }}<span class="text-sm font-normal text-gray-400 ml-1">bpm</span></p>
                        <p class="text-xs text-gray-400 mt-0.5">Pulse</p>
                        @if ($pulseDeviation !== null)
                            <p @class(['text-xs mt-0.5', 'text-green-600' => abs($pulseDeviation) <= 15, 'text-red-500' => abs($pulseDeviation) > 15])>
                                {{ $pulseDeviation > 0 ? '+' : '' }}{{ $pulseDeviation }}%
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            @if ($today->systolic && $today->diastolic)
                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-900">{{ $today->systolic }}/{{ $today->diastolic }}</span>
                        <span class="text-xs text-gray-400 ml-1">mmHg</span>
                    </div>
                    @if ($bpVariance)
                        <div class="flex gap-3 text-xs">
                            <span @class(['px-2 py-0.5 rounded-full', 'bg-red-100 text-red-700' => $bpVariance->systolicExceedsThreshold, 'bg-green-100 text-green-700' => !$bpVariance->systolicExceedsThreshold])>
                                SYS {{ $bpVariance->systolicPercent > 0 ? '+' : '' }}{{ round($bpVariance->systolicPercent, 1) }}%
                            </span>
                            <span @class(['px-2 py-0.5 rounded-full', 'bg-red-100 text-red-700' => $bpVariance->diastolicExceedsThreshold, 'bg-green-100 text-green-700' => !$bpVariance->diastolicExceedsThreshold])>
                                DIA {{ $bpVariance->diastolicPercent > 0 ? '+' : '' }}{{ round($bpVariance->diastolicPercent, 1) }}%
                            </span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Goal rings --}}
    @if ($caloriePct !== null || $waterPct !== null || $exercisePct !== null)
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-6">Daily Goals</h2>
            <div class="flex flex-wrap justify-around gap-6">
                @if ($caloriePct !== null)
                    @include('partials.goal-ring', [
                        'pct'     => $caloriePct,
                        'label'   => 'Calories',
                        'value'   => number_format($todayCalories).' kcal',
                        'goal'    => 'of '.number_format($calorieGoal),
                        'color'   => $caloriePct >= 100 ? '#ef4444' : '#6366f1',
                    ])
                @endif
                @if ($waterPct !== null)
                    @include('partials.goal-ring', [
                        'pct'   => $waterPct,
                        'label' => 'Water',
                        'value' => $todayWater.' L',
                        'goal'  => 'of '.$waterGoal.' L',
                        'color' => '#06b6d4',
                    ])
                @endif
                @if ($exercisePct !== null)
                    @include('partials.goal-ring', [
                        'pct'   => $exercisePct,
                        'label' => 'Exercise',
                        'value' => $todayExercise.' min',
                        'goal'  => 'of '.$exerciseGoal.' min',
                        'color' => '#10b981',
                    ])
                @endif
            </div>
        </div>
    @endif

    {{-- Charts --}}
    @if (count(array_filter($weightData)) > 1)
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-4">Weight</h2>
                <canvas id="weightChart" height="200"></canvas>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-4">BMI & Body Fat %</h2>
                <canvas id="compositionChart" height="200"></canvas>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-4">Blood Pressure</h2>
                <canvas id="bpChart" height="200"></canvas>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-4">Pulse</h2>
                <canvas id="pulseChart" height="200"></canvas>
            </div>
        </div>

        @php
            $chartConfig = json_encode([
                'labels'       => $chartLabels,
                'weight'       => $weightData,
                'bmi'          => $bmiData,
                'bfp'          => $bfpData,
                'systolic'     => $systolicData,
                'diastolic'    => $diastolicData,
                'pulse'        => $pulseData,
                'targetWeight'    => $profile?->target_weight ? (float)$profile->target_weight : null,
                'baselineWeight'  => $profile?->baseline_weight ? (float)$profile->baseline_weight : null,
                'basePulse'    => $profile?->baseline_pulse,
                'baseSystolic' => $profile?->baseline_systolic,
                'baseDiastolic'=> $profile?->baseline_diastolic,
            ]);
        @endphp

        <script>
        (function () {
            const d = @json(json_decode($chartConfig));

            const defaults = {
                tension: 0.3,
                pointRadius: 3,
                spanGaps: true,
            };

            const goalLine = (label, value, color, dash = [6, 3]) => value == null ? null : {
                label,
                data: d.labels.map(() => value),
                borderColor: color,
                borderDash: dash,
                pointRadius: 0,
                borderWidth: 1.5,
            };

            const makeChart = (id, datasets, yLabel) => {
                const ctx = document.getElementById(id);
                if (!ctx) return;
                new Chart(ctx, {
                    type: 'line',
                    data: { labels: d.labels, datasets: datasets.filter(Boolean) },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                        scales: {
                            x: { ticks: { font: { size: 11 } } },
                            y: { ticks: { font: { size: 11 } }, title: { display: !!yLabel, text: yLabel, font: { size: 11 } } },
                        },
                    },
                });
            };

            document.addEventListener('livewire:navigated', () => {
                makeChart('weightChart', [
                    { ...defaults, label: 'Weight (kg)', data: d.weight, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)' },
                    goalLine('Target', d.targetWeight, '#6366f1'),
                    goalLine('Starting', d.baselineWeight, '#94a3b8', [4, 2]),
                ]);

                (() => {
                    const ctx = document.getElementById('compositionChart');
                    if (!ctx) return;
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: d.labels,
                            datasets: [
                                { ...defaults, label: 'BMI', data: d.bmi, borderColor: '#f59e0b', yAxisID: 'y' },
                                { ...defaults, label: 'Body Fat %', data: d.bfp, borderColor: '#ec4899', yAxisID: 'y1' },
                            ],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                            scales: {
                                x: { ticks: { font: { size: 11 } } },
                                y:  { position: 'left',  ticks: { font: { size: 11 } }, title: { display: true, text: 'BMI', font: { size: 11 } } },
                                y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 11 } }, title: { display: true, text: 'BF%', font: { size: 11 } } },
                            },
                        },
                    });
                })();

                makeChart('bpChart', [
                    { ...defaults, label: 'Systolic', data: d.systolic, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.06)' },
                    { ...defaults, label: 'Diastolic', data: d.diastolic, borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.06)' },
                    goalLine('Baseline SYS', d.baseSystolic, '#ef4444'),
                    goalLine('Baseline DIA', d.baseDiastolic, '#f97316'),
                ]);

                makeChart('pulseChart', [
                    { ...defaults, label: 'Pulse (bpm)', data: d.pulse, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.08)' },
                    goalLine('Baseline', d.basePulse, '#8b5cf6'),
                ]);
            }, { once: true });
        })();
        </script>
    @else
        <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-400">
            Charts will appear once you have at least two records with weight data.
        </div>
    @endif

    {{-- Daily entry components --}}
    <livewire:daily-record :date="$date" :key="'record-'.$date" />
    <livewire:meal-log :date="$date" :key="'meals-'.$date" />

</div>
