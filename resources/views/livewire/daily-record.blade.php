<?php

use App\Models\HealthRecord;
use Livewire\Volt\Component;

new class extends Component {
    public string $date = '';

    public string $weight = '';
    public string $neck = '';
    public string $waist = '';
    public string $hip = '';
    public string $systolic = '';
    public string $diastolic = '';
    public string $pulse = '';
    public string $water_intake_l = '';
    public string $exercise_minutes = '';

    public bool $editing = false;
    public ?int $recordId = null;

    public function mount(string $date): void
    {
        $this->date = $date;
        $this->loadRecord();
    }

    public function edit(): void
    {
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->loadRecord();
    }

    public function save(): void
    {
        $data = $this->validate([
            'date'             => ['required', 'date'],
            'weight'           => ['nullable', 'numeric', 'min:20', 'max:500'],
            'neck'             => ['nullable', 'numeric', 'min:20', 'max:100'],
            'waist'            => ['nullable', 'numeric', 'min:40', 'max:300'],
            'hip'              => ['nullable', 'numeric', 'min:40', 'max:300'],
            'systolic'         => ['nullable', 'integer', 'min:60', 'max:300'],
            'diastolic'        => ['nullable', 'integer', 'min:30', 'max:200'],
            'pulse'            => ['nullable', 'integer', 'min:30', 'max:250'],
            'water_intake_l'   => ['nullable', 'numeric', 'min:0', 'max:20'],
            'exercise_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ]);

        $payload = collect($data)->except('date')->map(fn ($v) => $v === '' ? null : $v)->all();

        if ($this->recordId) {
            HealthRecord::find($this->recordId)?->update($payload);
        } else {
            $record = HealthRecord::create(['date' => $this->date, ...$payload]);
            $this->recordId = $record->id;
        }

        $this->editing = false;
        $this->dispatch('dashboard-refresh');
    }

    public function delete(): void
    {
        if ($this->recordId) {
            HealthRecord::find($this->recordId)?->delete();
            $this->recordId = null;
        }

        $this->resetFields();
    }

    private function loadRecord(): void
    {
        $record = HealthRecord::whereDate('date', $this->date)->first();

        if ($record) {
            $this->recordId       = $record->id;
            $this->weight         = (string) ($record->weight ?? '');
            $this->neck           = (string) ($record->neck ?? '');
            $this->waist          = (string) ($record->waist ?? '');
            $this->hip            = (string) ($record->hip ?? '');
            $this->systolic       = (string) ($record->systolic ?? '');
            $this->diastolic      = (string) ($record->diastolic ?? '');
            $this->pulse          = (string) ($record->pulse ?? '');
            $this->water_intake_l = (string) ($record->water_intake_l ?? '');
            $this->exercise_minutes = (string) ($record->exercise_minutes ?? '');
        } else {
            $this->recordId = null;
            $this->resetFields();
        }

        $this->editing = false;
    }

    private function resetFields(): void
    {
        $this->weight = $this->neck = $this->waist = $this->hip = '';
        $this->systolic = $this->diastolic = $this->pulse = '';
        $this->water_intake_l = $this->exercise_minutes = '';
    }
}; ?>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-semibold">Daily Record</h2>
        <span class="text-sm text-gray-400">{{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</span>
    </div>

    @if ($editing)
        <form wire:submit="save" class="space-y-5">
            {{-- Body --}}
            <fieldset>
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Body</legend>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ([
                        ['weight', 'Weight (kg)', '0.1'],
                        ['neck',   'Neck (cm)',    '0.1'],
                        ['waist',  'Waist (cm)',   '0.1'],
                        ['hip',    'Hip (cm)',     '0.1'],
                    ] as [$field, $label, $step])
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                            <input type="number" step="{{ $step }}" wire:model="{{ $field }}"
                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error($field) <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>

            {{-- Vitals --}}
            <fieldset>
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Vitals</legend>
                <div class="grid grid-cols-3 gap-3">
                    @foreach ([
                        ['systolic',  'Systolic'],
                        ['diastolic', 'Diastolic'],
                        ['pulse',     'Pulse (bpm)'],
                    ] as [$field, $label])
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">{{ $label }}</label>
                            <input type="number" wire:model="{{ $field }}"
                                class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error($field) <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>

            {{-- Activity --}}
            <fieldset>
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Activity</legend>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Water (L)</label>
                        <input type="number" step="0.1" wire:model="water_intake_l"
                            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('water_intake_l') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Exercise (min)</label>
                        <input type="number" wire:model="exercise_minutes"
                            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('exercise_minutes') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                </div>
            </fieldset>

            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    Save
                </button>
                <button type="button" wire:click="cancel"
                    class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    @else
        @if ($recordId)
            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-3 mb-5">
                @foreach ([
                    'Weight'    => $weight ? $weight.' kg' : null,
                    'Neck'      => $neck ? $neck.' cm' : null,
                    'Waist'     => $waist ? $waist.' cm' : null,
                    'Hip'       => $hip ? $hip.' cm' : null,
                    'Systolic'  => $systolic ? $systolic.' mmHg' : null,
                    'Diastolic' => $diastolic ? $diastolic.' mmHg' : null,
                    'Pulse'     => $pulse ? $pulse.' bpm' : null,
                    'Water'     => $water_intake_l ? $water_intake_l.' L' : null,
                    'Exercise'  => $exercise_minutes ? $exercise_minutes.' min' : null,
                ] as $label => $value)
                    @if ($value !== null)
                        <div>
                            <dt class="text-gray-400 text-xs">{{ $label }}</dt>
                            <dd class="font-medium">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
            </dl>
            <div class="flex gap-3">
                <button wire:click="edit"
                    class="px-4 py-1.5 text-sm font-medium text-indigo-600 border border-indigo-300 rounded-lg hover:bg-indigo-50">
                    Edit
                </button>
                <button wire:click="delete" wire:confirm="Delete this record?"
                    class="px-4 py-1.5 text-sm font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                    Delete
                </button>
            </div>
        @else
            <p class="text-sm text-gray-400 mb-4">No record for this date yet.</p>
            <button wire:click="edit"
                class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                Add Record
            </button>
        @endif
    @endif
</div>
