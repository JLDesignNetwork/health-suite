<?php

use App\Enums\Gender;
use App\Models\Profile;
use App\Models\Setting;
use App\Services\HealthService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.onboarding')]
#[Title('Get Started')]
class extends Component {
    public int $step = 1;

    // Step 1 — Biometrics
    public string $gender = '';
    public string $dob = '';
    public string $height_cm = '';

    // Step 2 — Starting Measurements
    public string $baseline_weight = '';
    public string $baseline_neck = '';
    public string $baseline_waist = '';
    public string $baseline_hip = '';

    // Step 3 — Physiological Norms
    public string $baseline_pulse = '';
    public string $baseline_systolic = '';
    public string $baseline_diastolic = '';

    // Step 4 — Goals
    public string $target_weight = '';
    public string $daily_calorie_goal = '';
    public string $daily_water_goal = '';
    public string $weekly_exercise_goal = '';

    public function nextStep(): void
    {
        $this->validateStep();
        $this->step++;
    }

    public function prevStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function save(): void
    {
        $this->validateStep();

        Profile::create([
            'gender'               => $this->gender,
            'dob'                  => $this->dob,
            'height_cm'            => $this->height_cm,
            'baseline_weight'      => $this->baseline_weight,
            'baseline_neck'        => $this->baseline_neck,
            'baseline_waist'       => $this->baseline_waist,
            'baseline_hip'         => $this->baseline_hip ?: null,
            'baseline_pulse'       => $this->baseline_pulse,
            'baseline_systolic'    => $this->baseline_systolic,
            'baseline_diastolic'   => $this->baseline_diastolic,
            'target_weight'        => $this->target_weight ?: null,
            'daily_calorie_goal'   => $this->daily_calorie_goal ?: null,
            'daily_water_goal'     => $this->daily_water_goal ?: null,
            'weekly_exercise_goal' => $this->weekly_exercise_goal ?: null,
        ]);

        $destination = Setting::get('auth_mode', 'login') === 'household'
            ? route('household')
            : route('dashboard');

        $this->redirect($destination, navigate: true);
    }

    public function previewBmi(): ?float
    {
        if (! $this->baseline_weight || ! $this->height_cm) {
            return null;
        }

        return round(app(HealthService::class)->bmi((float) $this->baseline_weight, (float) $this->height_cm), 1);
    }

    public function previewBfp(): ?float
    {
        if (! $this->gender || ! $this->baseline_waist || ! $this->baseline_neck || ! $this->height_cm) {
            return null;
        }

        if ($this->gender === Gender::Female->value && ! $this->baseline_hip) {
            return null;
        }

        try {
            return round(app(HealthService::class)->bodyFatPercent(
                Gender::from($this->gender),
                (float) $this->baseline_waist,
                (float) $this->baseline_neck,
                (float) $this->height_cm,
                $this->baseline_hip ? (float) $this->baseline_hip : null,
            ), 1);
        } catch (\ValueError) {
            return null;
        }
    }

    private function validateStep(): void
    {
        match ($this->step) {
            1 => $this->validate([
                'gender'    => ['required', 'in:male,female'],
                'dob'       => ['required', 'date', 'before:'.now()->subYears(10)->toDateString(), 'after:'.now()->subYears(120)->toDateString()],
                'height_cm' => ['required', 'numeric', 'min:50', 'max:300'],
            ]),
            2 => $this->validate([
                'baseline_weight' => ['required', 'numeric', 'min:20', 'max:500'],
                'baseline_neck'   => ['required', 'numeric', 'min:20', 'max:100'],
                'baseline_waist'  => ['required', 'numeric', 'min:40', 'max:300'],
                'baseline_hip'    => $this->gender === Gender::Female->value
                    ? ['required', 'numeric', 'min:40', 'max:300']
                    : ['nullable', 'numeric'],
            ]),
            3 => $this->validate([
                'baseline_pulse'     => ['required', 'integer', 'min:30', 'max:250'],
                'baseline_systolic'  => ['required', 'integer', 'min:60', 'max:300'],
                'baseline_diastolic' => ['required', 'integer', 'min:30', 'max:200'],
            ]),
            4 => $this->validate([
                'target_weight'        => ['nullable', 'numeric', 'min:20', 'max:500'],
                'daily_calorie_goal'   => ['nullable', 'integer', 'min:500', 'max:10000'],
                'daily_water_goal'     => ['nullable', 'numeric', 'min:0.5', 'max:20'],
                'weekly_exercise_goal' => ['nullable', 'integer', 'min:0', 'max:10080'],
            ]),
            default => null,
        };
    }
}; ?>

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">

    {{-- Progress --}}
    <div class="flex items-center mb-8">
        @foreach ([1 => 'Biometrics', 2 => 'Measurements', 3 => 'Norms', 4 => 'Goals', 5 => 'Review'] as $n => $label)
            <div class="flex flex-col items-center gap-1 shrink-0">
                <div @class([
                    'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold',
                    'bg-indigo-600 text-white' => $step >= $n,
                    'bg-gray-200 text-gray-400' => $step < $n,
                ])>{{ $n }}</div>
                <span class="text-xs text-gray-500">{{ $label }}</span>
            </div>
            @if ($n < 5)
                <div @class(['flex-1 h-0.5 mb-4 mx-2', 'bg-indigo-600' => $step > $n, 'bg-gray-200' => $step <= $n])></div>
            @endif
        @endforeach
    </div>

    {{-- Step 1 — Biometrics --}}
    @if ($step === 1)
        <h2 class="text-xl font-semibold mb-6">Your Biometrics</h2>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <div class="flex gap-6">
                    @foreach (\App\Enums\Gender::cases() as $g)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model="gender" value="{{ $g->value }}"
                                class="text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm">{{ $g->label() }}</span>
                        </label>
                    @endforeach
                </div>
                @error('gender') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                <input type="date" wire:model="dob"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('dob') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Height (cm)</label>
                <input type="number" step="0.1" wire:model="height_cm" placeholder="175.0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('height_cm') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    @endif

    {{-- Step 2 — Starting Measurements --}}
    @if ($step === 2)
        <h2 class="text-xl font-semibold mb-6">Starting Measurements</h2>
        <div class="space-y-4">
            @foreach ([
                ['baseline_weight', 'Weight (kg)',             '0.1', '75.0'],
                ['baseline_neck',   'Neck circumference (cm)', '0.1', '38.0'],
                ['baseline_waist',  'Waist circumference (cm)','0.1', '85.0'],
            ] as [$fieldName, $fieldLabel, $fieldStep, $fieldPlaceholder])
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $fieldLabel }}</label>
                    <input type="number" step="{{ $fieldStep }}" wire:model="{{ $fieldName }}" placeholder="{{ $fieldPlaceholder }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error($fieldName) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach

            @if ($gender === \App\Enums\Gender::Female->value)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hip circumference (cm)</label>
                    <input type="number" step="0.1" wire:model="baseline_hip" placeholder="100.0"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('baseline_hip') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>
    @endif

    {{-- Step 3 — Physiological Norms --}}
    @if ($step === 3)
        <h2 class="text-xl font-semibold mb-6">Resting Baselines</h2>
        <p class="text-sm text-gray-500 mb-4">Your healthy resting values — used to flag deviations in daily readings.</p>
        <div class="space-y-4">
            @foreach ([
                ['baseline_pulse',      'Resting pulse (bpm)', '60'],
                ['baseline_systolic',   'Systolic BP (mmHg)',  '120'],
                ['baseline_diastolic',  'Diastolic BP (mmHg)', '80'],
            ] as [$fieldName, $fieldLabel, $fieldPlaceholder])
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $fieldLabel }}</label>
                    <input type="number" wire:model="{{ $fieldName }}" placeholder="{{ $fieldPlaceholder }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error($fieldName) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    @endif

    {{-- Step 4 — Goals --}}
    @if ($step === 4)
        <h2 class="text-xl font-semibold mb-2">Your Goals</h2>
        <p class="text-sm text-gray-500 mb-4">All optional — you can set or update these later.</p>
        <div class="space-y-4">
            @foreach ([
                ['target_weight',        'Target weight (kg)',         '0.1', '70.0'],
                ['daily_calorie_goal',   'Daily calorie goal (kcal)',  '1',   '2000'],
                ['daily_water_goal',     'Daily water goal (L)',       '0.1', '2.5'],
                ['weekly_exercise_goal', 'Weekly exercise goal (min)', '1',   '150'],
            ] as [$fieldName, $fieldLabel, $fieldStep, $fieldPlaceholder])
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $fieldLabel }}</label>
                    <input type="number" step="{{ $fieldStep }}" wire:model="{{ $fieldName }}" placeholder="{{ $fieldPlaceholder }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error($fieldName) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    @endif

    {{-- Step 5 — Review --}}
    @if ($step === 5)
        <h2 class="text-xl font-semibold mb-6">Review & Confirm</h2>

        <dl class="divide-y divide-gray-100 text-sm mb-6">
            @foreach ([
                'Gender'        => ucfirst($gender),
                'Date of Birth' => $dob,
                'Height'        => $height_cm ? $height_cm.' cm' : '—',
                'Weight'        => $baseline_weight ? $baseline_weight.' kg' : '—',
                'Neck'          => $baseline_neck ? $baseline_neck.' cm' : '—',
                'Waist'         => $baseline_waist ? $baseline_waist.' cm' : '—',
                'Hip'           => $baseline_hip ? $baseline_hip.' cm' : '—',
                'Resting Pulse' => $baseline_pulse ? $baseline_pulse.' bpm' : '—',
                'Systolic BP'   => $baseline_systolic ? $baseline_systolic.' mmHg' : '—',
                'Diastolic BP'  => $baseline_diastolic ? $baseline_diastolic.' mmHg' : '—',
                'Target Weight' => $target_weight ? $target_weight.' kg' : '—',
                'Calorie Goal'  => $daily_calorie_goal ? $daily_calorie_goal.' kcal' : '—',
                'Water Goal'    => $daily_water_goal ? $daily_water_goal.' L' : '—',
                'Exercise Goal' => $weekly_exercise_goal ? $weekly_exercise_goal.' min/week' : '—',
            ] as $reviewLabel => $reviewValue)
                <div class="flex justify-between py-2">
                    <dt class="text-gray-500">{{ $reviewLabel }}</dt>
                    <dd class="font-medium">{{ $reviewValue }}</dd>
                </div>
            @endforeach
        </dl>

        @if ($bmi = $this->previewBmi())
            <div class="bg-indigo-50 rounded-xl p-4 flex gap-8 mb-4">
                <div class="text-center">
                    <p class="text-xs text-indigo-500 uppercase tracking-wide">BMI</p>
                    <p class="text-2xl font-bold text-indigo-700">{{ $bmi }}</p>
                </div>
                @if ($bfp = $this->previewBfp())
                    <div class="text-center">
                        <p class="text-xs text-indigo-500 uppercase tracking-wide">Body Fat</p>
                        <p class="text-2xl font-bold text-indigo-700">{{ $bfp }}%</p>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Navigation --}}
    <div class="flex justify-between mt-8">
        @if ($step > 1)
            <button type="button" wire:click="prevStep"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                Back
            </button>
        @else
            <div></div>
        @endif

        @if ($step < 5)
            <button type="button" wire:click="nextStep"
                class="px-6 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                Continue
            </button>
        @else
            <button type="button" wire:click="save"
                class="px-6 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                Save &amp; Get Started
            </button>
        @endif
    </div>

</div>
