<?php

use App\Enums\Gender;
use App\Models\PersonalInfo;
use App\Models\LifestyleProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('My Profile')]
class extends Component {
    // Read-only — physiological baselines
    public int $baseline_pulse;
    public int $baseline_systolic;
    public int $baseline_diastolic;

    // Editable — biometrics
    public string $gender       = '';
    public string $dob          = '';
    public string $height_cm    = '';

    // Editable — starting measurements
    public string $baseline_weight = '';
    public string $baseline_neck   = '';
    public string $baseline_waist  = '';
    public string $baseline_hip    = '';

    // Editable — goals
    public string $target_weight        = '';
    public string $daily_calorie_goal   = '';
    public string $daily_water_goal     = '';
    public string $weekly_exercise_goal = '';

    // Editable — personal & emergency info
    public string $bloodType             = '';
    public string $pronouns              = '';
    public string $ec1Name               = '';
    public string $ec1Relationship       = '';
    public string $ec1Phone              = '';
    public string $ec2Name               = '';
    public string $ec2Relationship       = '';
    public string $ec2Phone              = '';
    public string $primaryCarePhysician  = '';
    public string $pcpPhone              = '';
    public string $insuranceProvider     = '';
    public string $insuranceMemberId     = '';
    public string $insuranceGroupNumber  = '';
    public string $insurancePhone        = '';
    public string $patientNotes          = '';

    // Editable — lifestyle profile
    public string $dietaryRegimen   = '';
    public string $foodRestrictions = '';
    public string $caffeineIntake   = '';
    public string $physicalActivity = '';
    public string $sleepHours       = '';
    public string $sleepNotes       = '';
    public string $tobaccoUse       = '';
    public string $alcoholUse       = '';
    public string $substanceNotes   = '';
    public string $wellnessGoals    = '';

    public function mount(): void
    {
        $profile = auth()->user()->profile;

        $this->baseline_pulse     = $profile->baseline_pulse;
        $this->baseline_systolic  = $profile->baseline_systolic;
        $this->baseline_diastolic = $profile->baseline_diastolic;

        $this->gender    = $profile->gender->value;
        $this->dob       = $profile->dob->format('Y-m-d');
        $this->height_cm = (string) $profile->height_cm;

        $this->baseline_weight = (string) $profile->baseline_weight;
        $this->baseline_neck   = (string) $profile->baseline_neck;
        $this->baseline_waist  = (string) $profile->baseline_waist;
        $this->baseline_hip    = (string) ($profile->baseline_hip ?? '');

        $this->target_weight        = (string) ($profile->target_weight ?? '');
        $this->daily_calorie_goal   = (string) ($profile->daily_calorie_goal ?? '');
        $this->daily_water_goal     = (string) ($profile->daily_water_goal ?? '');
        $this->weekly_exercise_goal = (string) ($profile->weekly_exercise_goal ?? '');

        $info = auth()->user()->personalInfo;
        if ($info) {
            $this->bloodType            = $info->blood_type ?? '';
            $this->pronouns             = $info->pronouns ?? '';
            $this->ec1Name              = $info->emergency_contact_1_name ?? '';
            $this->ec1Relationship      = $info->emergency_contact_1_relationship ?? '';
            $this->ec1Phone             = $info->emergency_contact_1_phone ?? '';
            $this->ec2Name              = $info->emergency_contact_2_name ?? '';
            $this->ec2Relationship      = $info->emergency_contact_2_relationship ?? '';
            $this->ec2Phone             = $info->emergency_contact_2_phone ?? '';
            $this->primaryCarePhysician = $info->primary_care_physician ?? '';
            $this->pcpPhone             = $info->pcp_phone ?? '';
            $this->insuranceProvider    = $info->insurance_provider ?? '';
            $this->insuranceMemberId    = $info->insurance_member_id ?? '';
            $this->insuranceGroupNumber = $info->insurance_group_number ?? '';
            $this->insurancePhone       = $info->insurance_phone ?? '';
            $this->patientNotes         = $info->patient_notes ?? '';
        }

        $lifestyle = auth()->user()->lifestyleProfile;
        if ($lifestyle) {
            $this->dietaryRegimen   = $lifestyle->dietary_regimen ?? '';
            $this->foodRestrictions = $lifestyle->food_restrictions ?? '';
            $this->caffeineIntake   = $lifestyle->caffeine_intake ?? '';
            $this->physicalActivity = $lifestyle->physical_activity ?? '';
            $this->sleepHours       = (string) ($lifestyle->sleep_hours ?? '');
            $this->sleepNotes       = $lifestyle->sleep_notes ?? '';
            $this->tobaccoUse       = $lifestyle->tobacco_use ?? '';
            $this->alcoholUse       = $lifestyle->alcohol_use ?? '';
            $this->substanceNotes   = $lifestyle->substance_notes ?? '';
            $this->wellnessGoals    = $lifestyle->wellness_goals ?? '';
        }
    }

    public function save(): void
    {
        $this->validate([
            'gender'    => ['required', 'in:male,female'],
            'dob'       => ['required', 'date', 'before:'.now()->subYears(10)->toDateString(), 'after:'.now()->subYears(120)->toDateString()],
            'height_cm' => ['required', 'numeric', 'min:50', 'max:300'],

            'baseline_weight' => ['required', 'numeric', 'min:20', 'max:500'],
            'baseline_neck'   => ['required', 'numeric', 'min:20', 'max:100'],
            'baseline_waist'  => ['required', 'numeric', 'min:40', 'max:300'],
            'baseline_hip'    => $this->gender === Gender::Female->value
                ? ['required', 'numeric', 'min:40', 'max:300']
                : ['nullable', 'numeric'],

            'target_weight'        => ['nullable', 'numeric', 'min:20', 'max:500'],
            'daily_calorie_goal'   => ['nullable', 'integer', 'min:500', 'max:10000'],
            'daily_water_goal'     => ['nullable', 'numeric', 'min:0.5', 'max:20'],
            'weekly_exercise_goal' => ['nullable', 'integer', 'min:0', 'max:10080'],
        ]);

        auth()->user()->profile->update([
            'gender'    => $this->gender,
            'dob'       => $this->dob,
            'height_cm' => $this->height_cm,

            'baseline_weight' => $this->baseline_weight,
            'baseline_neck'   => $this->baseline_neck,
            'baseline_waist'  => $this->baseline_waist,
            'baseline_hip'    => $this->baseline_hip ?: null,

            'target_weight'        => $this->target_weight ?: null,
            'daily_calorie_goal'   => $this->daily_calorie_goal ?: null,
            'daily_water_goal'     => $this->daily_water_goal ?: null,
            'weekly_exercise_goal' => $this->weekly_exercise_goal ?: null,
        ]);

        $this->dispatch('profile-saved');
    }

    public function savePersonalInfo(): void
    {
        $this->validate([
            'bloodType' => ['nullable', 'string', 'max:10'],
            'pronouns'  => ['nullable', 'string', 'max:100'],
            'ec1Name'   => ['nullable', 'string', 'max:255'],
            'ec1Phone'  => ['nullable', 'string', 'max:50'],
            'ec2Name'   => ['nullable', 'string', 'max:255'],
            'ec2Phone'  => ['nullable', 'string', 'max:50'],
        ]);

        auth()->user()->personalInfo()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'blood_type'                       => $this->bloodType ?: null,
                'pronouns'                         => $this->pronouns ?: null,
                'emergency_contact_1_name'         => $this->ec1Name ?: null,
                'emergency_contact_1_relationship' => $this->ec1Relationship ?: null,
                'emergency_contact_1_phone'        => $this->ec1Phone ?: null,
                'emergency_contact_2_name'         => $this->ec2Name ?: null,
                'emergency_contact_2_relationship' => $this->ec2Relationship ?: null,
                'emergency_contact_2_phone'        => $this->ec2Phone ?: null,
                'primary_care_physician'           => $this->primaryCarePhysician ?: null,
                'pcp_phone'                        => $this->pcpPhone ?: null,
                'insurance_provider'               => $this->insuranceProvider ?: null,
                'insurance_member_id'              => $this->insuranceMemberId ?: null,
                'insurance_group_number'           => $this->insuranceGroupNumber ?: null,
                'insurance_phone'                  => $this->insurancePhone ?: null,
                'patient_notes'                    => $this->patientNotes ?: null,
            ]
        );

        $this->dispatch('personal-info-saved');
    }

    public function saveLifestyle(): void
    {
        $this->validate([
            'sleepHours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'tobaccoUse' => ['nullable', 'in:Never,Former,Current'],
            'alcoholUse' => ['nullable', 'in:None,Occasional,Moderate,Heavy'],
        ]);

        auth()->user()->lifestyleProfile()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'dietary_regimen'   => $this->dietaryRegimen ?: null,
                'food_restrictions' => $this->foodRestrictions ?: null,
                'caffeine_intake'   => $this->caffeineIntake ?: null,
                'physical_activity' => $this->physicalActivity ?: null,
                'sleep_hours'       => $this->sleepHours ?: null,
                'sleep_notes'       => $this->sleepNotes ?: null,
                'tobacco_use'       => $this->tobaccoUse ?: null,
                'alcohol_use'       => $this->alcoholUse ?: null,
                'substance_notes'   => $this->substanceNotes ?: null,
                'wellness_goals'    => $this->wellnessGoals ?: null,
            ]
        );

        $this->dispatch('lifestyle-saved');
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">My Profile</h1>

    {{-- Read-only: Physiological Baselines --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide mb-4">Physiological Baselines
            <span class="ml-2 text-xs font-normal text-gray-400 normal-case">(read-only — set during onboarding)</span>
        </h2>
        <div class="grid grid-cols-3 gap-4 sm:grid-cols-3">
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-800">{{ $baseline_pulse }}<span class="text-sm font-normal text-gray-400 ml-1">bpm</span></p>
                <p class="text-xs text-gray-400 mt-0.5">Resting Pulse</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-800">{{ $baseline_systolic }}<span class="text-sm font-normal text-gray-400 ml-1">mmHg</span></p>
                <p class="text-xs text-gray-400 mt-0.5">Baseline Systolic</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-800">{{ $baseline_diastolic }}<span class="text-sm font-normal text-gray-400 ml-1">mmHg</span></p>
                <p class="text-xs text-gray-400 mt-0.5">Baseline Diastolic</p>
            </div>
        </div>
    </div>

    {{-- Editable form --}}
    <form wire:submit="save" class="space-y-6">

        {{-- Biometrics --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">Personal Information</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select wire:model="gender"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach (App\Enums\Gender::cases() as $g)
                            <option value="{{ $g->value }}">{{ ucfirst($g->value) }}</option>
                        @endforeach
                    </select>
                    @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                    <input type="date" wire:model="dob"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('dob') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Height</label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="height_cm" placeholder="175.0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">cm</span>
                    </div>
                    @error('height_cm') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Starting Measurements --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">Starting Measurements</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight</label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="baseline_weight"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kg</span>
                    </div>
                    @error('baseline_weight') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Neck</label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="baseline_neck"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">cm</span>
                    </div>
                    @error('baseline_neck') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Waist</label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="baseline_waist"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">cm</span>
                    </div>
                    @error('baseline_waist') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Hip
                        @if ($gender !== 'female')
                            <span class="text-xs font-normal text-gray-400">(optional)</span>
                        @endif
                    </label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="baseline_hip"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">cm</span>
                    </div>
                    @error('baseline_hip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Goals --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">Goals
                <span class="ml-2 text-xs font-normal text-gray-400 normal-case">(all optional)</span>
            </h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Weight</label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="target_weight"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kg</span>
                    </div>
                    @error('target_weight') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Daily Calories</label>
                    <div class="relative">
                        <input type="number" wire:model="daily_calorie_goal"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-14 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">kcal</span>
                    </div>
                    @error('daily_calorie_goal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Daily Water</label>
                    <div class="relative">
                        <input type="number" step="0.1" wire:model="daily_water_goal"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">L</span>
                    </div>
                    @error('daily_water_goal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weekly Exercise</label>
                    <div class="relative">
                        <input type="number" wire:model="weekly_exercise_goal"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">min</span>
                    </div>
                    @error('weekly_exercise_goal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Save --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Save Changes
            </button>
            <span
                x-data="{ show: false }"
                x-on:profile-saved.window="show = true; setTimeout(() => show = false, 2500)"
                x-show="show"
                x-transition.opacity
                class="text-sm text-green-600">
                Saved.
            </span>
        </div>
    </form>

    {{-- Personal & Emergency Information --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">Personal &amp; Emergency Information
            <span class="ml-2 text-xs font-normal text-gray-400 normal-case">(all optional)</span>
        </h2>

        {{-- Blood Type & Pronouns --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Blood Type</label>
                <input type="text" wire:model="bloodType" placeholder="e.g. A+"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('bloodType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pronouns</label>
                <input type="text" wire:model="pronouns" placeholder="e.g. he/him"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('pronouns') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div></div>
        </div>

        {{-- Primary Emergency Contact --}}
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide pt-2">Primary Emergency Contact</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" wire:model="ec1Name"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('ec1Name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Relationship</label>
                <input type="text" wire:model="ec1Relationship" placeholder="e.g. Spouse"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" wire:model="ec1Phone"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('ec1Phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Secondary Emergency Contact --}}
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide pt-2">Secondary Emergency Contact <span class="text-xs font-normal text-gray-400 normal-case">(optional)</span></p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" wire:model="ec2Name"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('ec2Name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Relationship</label>
                <input type="text" wire:model="ec2Relationship" placeholder="e.g. Parent"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" wire:model="ec2Phone"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('ec2Phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Primary Care Physician --}}
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide pt-2">Primary Care Physician</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Physician Name</label>
                <input type="text" wire:model="primaryCarePhysician"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" wire:model="pcpPhone"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Health Insurance --}}
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide pt-2">Health Insurance</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
                <input type="text" wire:model="insuranceProvider"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Member ID</label>
                <input type="text" wire:model="insuranceMemberId"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Group Number</label>
                <input type="text" wire:model="insuranceGroupNumber"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Insurance Phone</label>
                <input type="text" wire:model="insurancePhone"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Patient Notes --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Patient Notes</label>
            <textarea wire:model="patientNotes" rows="3"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Any additional notes for your medical team..."></textarea>
        </div>

        {{-- Save --}}
        <div class="flex items-center gap-3">
            <button type="button" wire:click="savePersonalInfo"
                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Save Personal Info
            </button>
            <span
                x-data="{ show: false }"
                x-on:personal-info-saved.window="show = true; setTimeout(() => show = false, 2500)"
                x-show="show"
                x-transition.opacity
                class="text-sm text-green-600">
                Saved.
            </span>
        </div>
    </div>

    {{-- Lifestyle Profile --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wide">Lifestyle Profile
            <span class="ml-2 text-xs font-normal text-gray-400 normal-case">(all optional)</span>
        </h2>

        {{-- Dietary --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dietary Regimen</label>
            <input type="text" wire:model="dietaryRegimen" placeholder="e.g. Mediterranean, Keto, Vegetarian"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Food Restrictions / Allergies
                <span class="text-xs font-normal text-gray-400">(separate from medical allergies)</span>
            </label>
            <textarea wire:model="foodRestrictions" rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="e.g. gluten-free, nut allergy, lactose intolerant..."></textarea>
        </div>

        {{-- Caffeine --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Caffeine Intake</label>
                <input type="text" wire:model="caffeineIntake" placeholder="e.g. 2 cups coffee/day"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div></div>
        </div>

        {{-- Physical Activity --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Physical Activity</label>
            <textarea wire:model="physicalActivity" rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Describe your typical exercise routine..."></textarea>
        </div>

        {{-- Sleep --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Avg Hours per Night</label>
                <input type="number" step="0.5" wire:model="sleepHours" min="0" max="24"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('sleepHours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sleep Notes</label>
                <input type="text" wire:model="sleepNotes" placeholder="e.g. uses CPAP, frequent waking"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Tobacco & Alcohol --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tobacco Use</label>
                <select wire:model="tobaccoUse"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value=""></option>
                    <option value="Never">Never</option>
                    <option value="Former">Former</option>
                    <option value="Current">Current</option>
                </select>
                @error('tobaccoUse') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alcohol Use</label>
                <select wire:model="alcoholUse"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value=""></option>
                    <option value="None">None</option>
                    <option value="Occasional">Occasional</option>
                    <option value="Moderate">Moderate</option>
                    <option value="Heavy">Heavy</option>
                </select>
                @error('alcoholUse') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Substance Notes --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Substance Use Notes</label>
            <textarea wire:model="substanceNotes" rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Any additional context..."></textarea>
        </div>

        {{-- Wellness Goals --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Primary Wellness Goals</label>
            <textarea wire:model="wellnessGoals" rows="3"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                placeholder="Describe what you're working toward..."></textarea>
        </div>

        {{-- Save --}}
        <div class="flex items-center gap-3">
            <button type="button" wire:click="saveLifestyle"
                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Save Lifestyle
            </button>
            <span
                x-data="{ show: false }"
                x-on:lifestyle-saved.window="show = true; setTimeout(() => show = false, 2500)"
                x-show="show"
                x-transition.opacity
                class="text-sm text-green-600">
                Saved.
            </span>
        </div>
    </div>
</div>
