<?php

use App\Models\Allergy;
use App\Models\Condition;
use App\Models\FamilyHistory;
use App\Models\Medication;
use App\Models\Screening;
use App\Models\Surgery;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Health Record')]
class extends Component {
    public string $activeSection = 'allergies';

    // ── Allergies ────────────────────────────────────────────────────────────
    public bool    $allergyShowForm  = false;
    public ?int    $allergyEditingId = null;
    public string  $allergyAllergen  = '';
    public string  $allergyCategory  = '';
    public string  $allergySeverity  = '';
    public string  $allergyReaction  = '';
    public string  $allergyTreatment = '';

    // ── Conditions ───────────────────────────────────────────────────────────
    public bool    $conditionShowForm  = false;
    public ?int    $conditionEditingId = null;
    public string  $conditionName      = '';
    public string  $conditionYear      = '';
    public string  $conditionStatus    = 'Active';
    public string  $conditionSpecialist = '';
    public string  $conditionNotes     = '';

    // ── Surgeries ────────────────────────────────────────────────────────────
    public bool    $surgeryShowForm  = false;
    public ?int    $surgeryEditingId = null;
    public string  $surgeryProcedure = '';
    public string  $surgeryDateYear  = '';
    public string  $surgeryFacility  = '';
    public string  $surgerySurgeon   = '';
    public string  $surgeryNotes     = '';

    // ── Family History ───────────────────────────────────────────────────────
    public bool    $familyShowForm  = false;
    public ?int    $familyEditingId = null;
    public string  $familyRelative  = '';
    public string  $familyConditions = '';
    public string  $familyOnset     = '';
    public string  $familyStatus    = '';

    // ── Screenings ───────────────────────────────────────────────────────────
    public bool    $screeningShowForm  = false;
    public ?int    $screeningEditingId = null;
    public string  $screeningType      = '';
    public string  $screeningLastDate  = '';
    public string  $screeningNextDate  = '';
    public string  $screeningProvider  = '';
    public string  $screeningNotes     = '';

    // ── Medications ──────────────────────────────────────────────────────────
    public bool    $medicationShowForm    = false;
    public ?int    $medicationEditingId   = null;
    public string  $medicationName        = '';
    public string  $medicationCategory    = '';
    public string  $medicationForm        = '';
    public string  $medicationDosage      = '';
    public string  $medicationFrequency   = '';
    public string  $medicationTiming      = '';
    public string  $medicationReason      = '';
    public string  $medicationDoctor      = '';
    public string  $medicationStartDate   = '';
    public string  $medicationStatus      = 'Active';
    public string  $medicationPillColor   = '';
    public string  $medicationPillShape   = '';
    public string  $medicationNotes       = '';

    // ── Medication lookup ────────────────────────────────────────────────────
    public string $lookupMedName = '';
    public string $lookupResult  = '';
    public string $lookupError   = '';

    // ── Section switcher ─────────────────────────────────────────────────────

    public function switchSection(string $section): void
    {
        $this->activeSection = $section;
        $this->resetForm();
    }

    // ── Allergies ────────────────────────────────────────────────────────────

    public function allergies(): \Illuminate\Database\Eloquent\Collection
    {
        return Allergy::orderBy('severity')->get();
    }

    public function addAllergy(): void
    {
        $this->resetForm();
        $this->allergyShowForm = true;
    }

    public function editAllergy(int $id): void
    {
        $this->resetForm();
        $record = Allergy::findOrFail($id);
        $this->allergyEditingId = $record->id;
        $this->allergyAllergen  = $record->allergen;
        $this->allergyCategory  = $record->category;
        $this->allergySeverity  = $record->severity;
        $this->allergyReaction  = $record->reaction ?? '';
        $this->allergyTreatment = $record->treatment ?? '';
        $this->allergyShowForm  = true;
    }

    public function saveAllergy(): void
    {
        $data = $this->validate([
            'allergyAllergen'  => ['required', 'string', 'max:255'],
            'allergyCategory'  => ['required', 'string', 'in:Medication,Food,Environmental,Other'],
            'allergySeverity'  => ['required', 'string', 'in:Mild,Moderate,Severe,Anaphylaxis'],
            'allergyReaction'  => ['nullable', 'string', 'max:1000'],
            'allergyTreatment' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'allergen'  => $data['allergyAllergen'],
            'category'  => $data['allergyCategory'],
            'severity'  => $data['allergySeverity'],
            'reaction'  => $data['allergyReaction'] ?: null,
            'treatment' => $data['allergyTreatment'] ?: null,
        ];

        if ($this->allergyEditingId) {
            Allergy::findOrFail($this->allergyEditingId)->update($payload);
        } else {
            Allergy::create($payload);
        }

        $this->resetForm();
    }

    public function deleteAllergy(int $id): void
    {
        Allergy::findOrFail($id)->delete();
    }

    // ── Conditions ───────────────────────────────────────────────────────────

    public function conditions(): \Illuminate\Database\Eloquent\Collection
    {
        return Condition::orderBy('status')->orderBy('name')->get();
    }

    public function addCondition(): void
    {
        $this->resetForm();
        $this->conditionShowForm = true;
    }

    public function editCondition(int $id): void
    {
        $this->resetForm();
        $record = Condition::findOrFail($id);
        $this->conditionEditingId  = $record->id;
        $this->conditionName       = $record->name;
        $this->conditionYear       = (string) ($record->diagnosis_year ?? '');
        $this->conditionStatus     = $record->status;
        $this->conditionSpecialist = $record->specialist ?? '';
        $this->conditionNotes      = $record->notes ?? '';
        $this->conditionShowForm   = true;
    }

    public function saveCondition(): void
    {
        $data = $this->validate([
            'conditionName'      => ['required', 'string', 'max:255'],
            'conditionYear'      => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'conditionStatus'    => ['required', 'string', 'in:Active,Managed,Remission,Resolved'],
            'conditionSpecialist' => ['nullable', 'string', 'max:255'],
            'conditionNotes'     => ['nullable', 'string'],
        ]);

        $payload = [
            'name'           => $data['conditionName'],
            'diagnosis_year' => $data['conditionYear'] ?: null,
            'status'         => $data['conditionStatus'],
            'specialist'     => $data['conditionSpecialist'] ?: null,
            'notes'          => $data['conditionNotes'] ?: null,
        ];

        if ($this->conditionEditingId) {
            Condition::findOrFail($this->conditionEditingId)->update($payload);
        } else {
            Condition::create($payload);
        }

        $this->resetForm();
    }

    public function deleteCondition(int $id): void
    {
        Condition::findOrFail($id)->delete();
    }

    // ── Surgeries ────────────────────────────────────────────────────────────

    public function surgeries(): \Illuminate\Database\Eloquent\Collection
    {
        return Surgery::orderBy('date_year', 'desc')->get();
    }

    public function addSurgery(): void
    {
        $this->resetForm();
        $this->surgeryShowForm = true;
    }

    public function editSurgery(int $id): void
    {
        $this->resetForm();
        $record = Surgery::findOrFail($id);
        $this->surgeryEditingId = $record->id;
        $this->surgeryProcedure = $record->procedure;
        $this->surgeryDateYear  = $record->date_year ?? '';
        $this->surgeryFacility  = $record->facility ?? '';
        $this->surgerySurgeon   = $record->surgeon ?? '';
        $this->surgeryNotes     = $record->notes ?? '';
        $this->surgeryShowForm  = true;
    }

    public function saveSurgery(): void
    {
        $data = $this->validate([
            'surgeryProcedure' => ['required', 'string', 'max:500'],
            'surgeryDateYear'  => ['nullable', 'string', 'max:50'],
            'surgeryFacility'  => ['nullable', 'string', 'max:255'],
            'surgerySurgeon'   => ['nullable', 'string', 'max:255'],
            'surgeryNotes'     => ['nullable', 'string'],
        ]);

        $payload = [
            'procedure' => $data['surgeryProcedure'],
            'date_year' => $data['surgeryDateYear'] ?: null,
            'facility'  => $data['surgeryFacility'] ?: null,
            'surgeon'   => $data['surgerySurgeon'] ?: null,
            'notes'     => $data['surgeryNotes'] ?: null,
        ];

        if ($this->surgeryEditingId) {
            Surgery::findOrFail($this->surgeryEditingId)->update($payload);
        } else {
            Surgery::create($payload);
        }

        $this->resetForm();
    }

    public function deleteSurgery(int $id): void
    {
        Surgery::findOrFail($id)->delete();
    }

    // ── Family History ───────────────────────────────────────────────────────

    public function familyHistories(): \Illuminate\Database\Eloquent\Collection
    {
        return FamilyHistory::orderBy('relative')->get();
    }

    public function addFamily(): void
    {
        $this->resetForm();
        $this->familyShowForm = true;
    }

    public function editFamily(int $id): void
    {
        $this->resetForm();
        $record = FamilyHistory::findOrFail($id);
        $this->familyEditingId  = $record->id;
        $this->familyRelative   = $record->relative;
        $this->familyConditions = $record->conditions;
        $this->familyOnset      = $record->onset ?? '';
        $this->familyStatus     = $record->status ?? '';
        $this->familyShowForm   = true;
    }

    public function saveFamily(): void
    {
        $data = $this->validate([
            'familyRelative'   => ['required', 'string', 'max:255'],
            'familyConditions' => ['required', 'string'],
            'familyOnset'      => ['nullable', 'string', 'max:255'],
            'familyStatus'     => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'relative'   => $data['familyRelative'],
            'conditions' => $data['familyConditions'],
            'onset'      => $data['familyOnset'] ?: null,
            'status'     => $data['familyStatus'] ?: null,
        ];

        if ($this->familyEditingId) {
            FamilyHistory::findOrFail($this->familyEditingId)->update($payload);
        } else {
            FamilyHistory::create($payload);
        }

        $this->resetForm();
    }

    public function deleteFamily(int $id): void
    {
        FamilyHistory::findOrFail($id)->delete();
    }

    // ── Screenings ───────────────────────────────────────────────────────────

    public function screenings(): \Illuminate\Database\Eloquent\Collection
    {
        return Screening::orderBy('next_due_date')->get();
    }

    public function addScreening(): void
    {
        $this->resetForm();
        $this->screeningShowForm = true;
    }

    public function editScreening(int $id): void
    {
        $this->resetForm();
        $record = Screening::findOrFail($id);
        $this->screeningEditingId = $record->id;
        $this->screeningType      = $record->screening_type;
        $this->screeningLastDate  = $record->last_date?->toDateString() ?? '';
        $this->screeningNextDate  = $record->next_due_date?->toDateString() ?? '';
        $this->screeningProvider  = $record->provider ?? '';
        $this->screeningNotes     = $record->notes ?? '';
        $this->screeningShowForm  = true;
    }

    public function saveScreening(): void
    {
        $data = $this->validate([
            'screeningType'     => ['required', 'string', 'max:255'],
            'screeningLastDate' => ['nullable', 'date'],
            'screeningNextDate' => ['nullable', 'date'],
            'screeningProvider' => ['nullable', 'string', 'max:255'],
            'screeningNotes'    => ['nullable', 'string'],
        ]);

        $payload = [
            'screening_type' => $data['screeningType'],
            'last_date'      => $data['screeningLastDate'] ?: null,
            'next_due_date'  => $data['screeningNextDate'] ?: null,
            'provider'       => $data['screeningProvider'] ?: null,
            'notes'          => $data['screeningNotes'] ?: null,
        ];

        if ($this->screeningEditingId) {
            Screening::findOrFail($this->screeningEditingId)->update($payload);
        } else {
            Screening::create($payload);
        }

        $this->resetForm();
    }

    public function deleteScreening(int $id): void
    {
        Screening::findOrFail($id)->delete();
    }

    // ── Medications ──────────────────────────────────────────────────────────

    public function medications(): \Illuminate\Database\Eloquent\Collection
    {
        return Medication::orderByRaw("CASE status WHEN 'Active' THEN 1 WHEN 'Paused' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();
    }

    public function addMedication(): void
    {
        $this->resetForm();
        $this->medicationShowForm = true;
    }

    public function editMedication(int $id): void
    {
        $this->resetForm();
        $record = Medication::findOrFail($id);
        $this->medicationEditingId = $record->id;
        $this->medicationName      = $record->name;
        $this->medicationCategory  = $record->category;
        $this->medicationForm      = $record->form;
        $this->medicationDosage    = $record->dosage;
        $this->medicationFrequency = $record->frequency;
        $this->medicationTiming    = $record->timing ?? '';
        $this->medicationReason    = $record->reason ?? '';
        $this->medicationDoctor    = $record->prescribing_doctor ?? '';
        $this->medicationStartDate = $record->start_date?->toDateString() ?? '';
        $this->medicationStatus    = $record->status;
        $this->medicationPillColor = $record->pill_color ?? '';
        $this->medicationPillShape = $record->pill_shape ?? '';
        $this->medicationNotes     = $record->notes ?? '';
        $this->medicationShowForm  = true;
    }

    public function saveMedication(): void
    {
        $data = $this->validate([
            'medicationName'      => ['required', 'string', 'max:255'],
            'medicationCategory'  => ['required', 'string', 'in:Prescription,OTC,Vitamin,Supplement,Hormone'],
            'medicationForm'      => ['required', 'string', 'in:Tablet,Capsule,Liquid,Injection,Topical,Powder,Other'],
            'medicationDosage'    => ['required', 'string', 'max:255'],
            'medicationFrequency' => ['required', 'string', 'max:255'],
            'medicationStatus'    => ['required', 'string', 'in:Active,Paused,Discontinued'],
            'medicationTiming'    => ['nullable', 'string', 'max:255'],
            'medicationReason'    => ['nullable', 'string'],
            'medicationDoctor'    => ['nullable', 'string', 'max:255'],
            'medicationStartDate' => ['nullable', 'date'],
            'medicationPillColor' => ['nullable', 'string', 'max:255'],
            'medicationPillShape' => ['nullable', 'string', 'max:255'],
            'medicationNotes'     => ['nullable', 'string'],
        ]);

        $payload = [
            'name'               => $data['medicationName'],
            'category'           => $data['medicationCategory'],
            'form'               => $data['medicationForm'],
            'dosage'             => $data['medicationDosage'],
            'frequency'          => $data['medicationFrequency'],
            'status'             => $data['medicationStatus'],
            'timing'             => $data['medicationTiming'] ?: null,
            'reason'             => $data['medicationReason'] ?: null,
            'prescribing_doctor' => $data['medicationDoctor'] ?: null,
            'start_date'         => $data['medicationStartDate'] ?: null,
            'pill_color'         => $data['medicationPillColor'] ?: null,
            'pill_shape'         => $data['medicationPillShape'] ?: null,
            'notes'              => $data['medicationNotes'] ?: null,
        ];

        if ($this->medicationEditingId) {
            Medication::findOrFail($this->medicationEditingId)->update($payload);
        } else {
            Medication::create($payload);
        }

        $this->resetForm();
    }

    public function deleteMedication(int $id): void
    {
        Medication::findOrFail($id)->delete();
    }

    public function lookupMedication(int $id): void
    {
        $med = Medication::findOrFail($id);

        $this->lookupMedName = $med->name;
        $this->lookupResult  = '';
        $this->lookupError   = '';

        $svc = app(\App\Services\AiService::class);

        if (! $svc->isConfigured()) {
            $this->lookupError = 'AI Assistant is not configured. Go to Settings → AI Assistant to add your API key.';
            return;
        }

        $prompt  = 'Provide a concise lookup for: '.$med->name;
        if ($med->dosage) $prompt .= ' ('.$med->dosage.')';
        $prompt .= "\n\nCover:\n1. Drug class and primary indication\n2. Common and notable side effects\n3. Known interactions with my other current medications (from my profile above)\n4. Timing — best taken with/without food, time of day\n5. Availability — prescription-only or OTC\n\nBe practical and concise.";

        try {
            $this->lookupResult = $svc->chat(auth()->user(), [['role' => 'user', 'content' => $prompt]]);
        } catch (\Throwable $e) {
            $this->lookupError = 'Error: '.$e->getMessage();
        }
    }

    public function closeLookup(): void
    {
        $this->lookupMedName = '';
        $this->lookupResult  = '';
        $this->lookupError   = '';
    }

    // ── cancel / resetForm ───────────────────────────────────────────────────

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        // Allergies
        $this->allergyShowForm  = false;
        $this->allergyEditingId = null;
        $this->allergyAllergen  = '';
        $this->allergyCategory  = '';
        $this->allergySeverity  = '';
        $this->allergyReaction  = '';
        $this->allergyTreatment = '';

        // Conditions
        $this->conditionShowForm   = false;
        $this->conditionEditingId  = null;
        $this->conditionName       = '';
        $this->conditionYear       = '';
        $this->conditionStatus     = 'Active';
        $this->conditionSpecialist = '';
        $this->conditionNotes      = '';

        // Surgeries
        $this->surgeryShowForm  = false;
        $this->surgeryEditingId = null;
        $this->surgeryProcedure = '';
        $this->surgeryDateYear  = '';
        $this->surgeryFacility  = '';
        $this->surgerySurgeon   = '';
        $this->surgeryNotes     = '';

        // Family History
        $this->familyShowForm   = false;
        $this->familyEditingId  = null;
        $this->familyRelative   = '';
        $this->familyConditions = '';
        $this->familyOnset      = '';
        $this->familyStatus     = '';

        // Screenings
        $this->screeningShowForm  = false;
        $this->screeningEditingId = null;
        $this->screeningType      = '';
        $this->screeningLastDate  = '';
        $this->screeningNextDate  = '';
        $this->screeningProvider  = '';
        $this->screeningNotes     = '';

        // Medications
        $this->medicationShowForm    = false;
        $this->medicationEditingId   = null;
        $this->lookupMedName         = '';
        $this->lookupResult          = '';
        $this->lookupError           = '';
        $this->medicationName        = '';
        $this->medicationCategory    = '';
        $this->medicationForm        = '';
        $this->medicationDosage      = '';
        $this->medicationFrequency   = '';
        $this->medicationTiming      = '';
        $this->medicationReason      = '';
        $this->medicationDoctor      = '';
        $this->medicationStartDate   = '';
        $this->medicationStatus      = 'Active';
        $this->medicationPillColor   = '';
        $this->medicationPillShape   = '';
        $this->medicationNotes       = '';
    }
}; ?>

<div class="space-y-6">

    {{-- Page heading --}}
    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Health Record</h1>

    {{-- Tab navigation --}}
    <div class="flex flex-wrap gap-1.5">
        @foreach ([
            'allergies'      => 'Allergies',
            'conditions'     => 'Conditions',
            'surgeries'      => 'Surgeries',
            'family'         => 'Family History',
            'screenings'     => 'Screenings',
            'medications'    => 'Medications',
        ] as $key => $label)
            <button wire:click="switchSection('{{ $key }}')"
                @class([
                    'px-4 py-2 text-sm font-medium rounded-full transition-colors',
                    'bg-indigo-600 text-white'                  => $activeSection === $key,
                    'text-gray-600 hover:bg-gray-100'           => $activeSection !== $key,
                ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ── ALLERGIES ──────────────────────────────────────────────────────── --}}
    @if ($activeSection === 'allergies')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">

            {{-- Section header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Allergies</h2>
                @if (! $allergyShowForm)
                    <button wire:click="addAllergy"
                        class="px-4 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        + Add Allergy
                    </button>
                @endif
            </div>

            {{-- Form --}}
            @if ($allergyShowForm)
                <form wire:submit="saveAllergy" class="bg-gray-50 rounded-xl p-4 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Allergen <span class="text-red-400">*</span></label>
                            <input type="text" wire:model="allergyAllergen" placeholder="e.g. Penicillin"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('allergyAllergen') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Category <span class="text-red-400">*</span></label>
                            <select wire:model="allergyCategory"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select category…</option>
                                <option>Medication</option>
                                <option>Food</option>
                                <option>Environmental</option>
                                <option>Other</option>
                            </select>
                            @error('allergyCategory') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Severity <span class="text-red-400">*</span></label>
                            <select wire:model="allergySeverity"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select severity…</option>
                                <option>Mild</option>
                                <option>Moderate</option>
                                <option>Severe</option>
                                <option>Anaphylaxis</option>
                            </select>
                            @error('allergySeverity') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Reaction</label>
                        <textarea wire:model="allergyReaction" rows="2" placeholder="Describe symptoms…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        @error('allergyReaction') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Treatment</label>
                        <textarea wire:model="allergyTreatment" rows="2" placeholder="e.g. EpiPen, avoid exposure…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        @error('allergyTreatment') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                            {{ $allergyEditingId ? 'Update' : 'Add' }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                </form>
            @endif

            {{-- List --}}
            @php $allergies = $this->allergies(); @endphp
            @if ($allergies->isEmpty())
                <p class="text-sm text-gray-400">No allergies recorded yet.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($allergies as $allergy)
                        @php
                            $severityClass = match ($allergy->severity) {
                                'Mild'        => 'bg-green-100 text-green-700',
                                'Moderate'    => 'bg-yellow-100 text-yellow-700',
                                'Severe'      => 'bg-orange-100 text-orange-700',
                                'Anaphylaxis' => 'bg-red-100 text-red-700',
                                default       => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <div class="flex items-start justify-between gap-4 py-3">
                            <div class="flex items-start gap-3 min-w-0">
                                <span class="mt-0.5 shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $severityClass }}">
                                    {{ $allergy->severity }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ $allergy->allergen }}</p>
                                    <p class="text-xs text-gray-400">{{ $allergy->category }}</p>
                                    @if ($allergy->reaction)
                                        <p class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ $allergy->reaction }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="editAllergy({{ $allergy->id }})"
                                    class="px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50">
                                    Edit
                                </button>
                                <button wire:click="deleteAllergy({{ $allergy->id }})" wire:confirm="Remove this record?"
                                    class="px-3 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ── CONDITIONS ─────────────────────────────────────────────────────── --}}
    @if ($activeSection === 'conditions')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">

            {{-- Section header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Conditions</h2>
                @if (! $conditionShowForm)
                    <button wire:click="addCondition"
                        class="px-4 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        + Add Condition
                    </button>
                @endif
            </div>

            {{-- Form --}}
            @if ($conditionShowForm)
                <form wire:submit="saveCondition" class="bg-gray-50 rounded-xl p-4 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Condition / Illness <span class="text-red-400">*</span></label>
                            <input type="text" wire:model="conditionName" placeholder="e.g. Type 2 Diabetes"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('conditionName') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Diagnosis Year</label>
                            <input type="number" wire:model="conditionYear" placeholder="e.g. 2018" min="1900" max="2100"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('conditionYear') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Status <span class="text-red-400">*</span></label>
                            <select wire:model="conditionStatus"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option>Active</option>
                                <option>Managed</option>
                                <option>Remission</option>
                                <option>Resolved</option>
                            </select>
                            @error('conditionStatus') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Primary Specialist</label>
                            <input type="text" wire:model="conditionSpecialist" placeholder="e.g. Dr. Smith, Endocrinologist"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('conditionSpecialist') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                        <textarea wire:model="conditionNotes" rows="3" placeholder="Additional notes…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        @error('conditionNotes') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                            {{ $conditionEditingId ? 'Update' : 'Add' }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                </form>
            @endif

            {{-- List --}}
            @php $conditions = $this->conditions(); @endphp
            @if ($conditions->isEmpty())
                <p class="text-sm text-gray-400">No conditions recorded yet.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($conditions as $condition)
                        @php
                            $condStatusClass = match ($condition->status) {
                                'Active'    => 'bg-red-100 text-red-700',
                                'Managed'   => 'bg-green-100 text-green-700',
                                'Remission' => 'bg-blue-100 text-blue-700',
                                'Resolved'  => 'bg-gray-100 text-gray-500',
                                default     => 'bg-gray-100 text-gray-500',
                            };
                        @endphp
                        <div class="flex items-start justify-between gap-4 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-medium text-gray-900">{{ $condition->name }}</p>
                                    @if ($condition->diagnosis_year)
                                        <span class="text-xs text-gray-400">({{ $condition->diagnosis_year }})</span>
                                    @endif
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $condStatusClass }}">
                                        {{ $condition->status }}
                                    </span>
                                </div>
                                @if ($condition->specialist)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $condition->specialist }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="editCondition({{ $condition->id }})"
                                    class="px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50">
                                    Edit
                                </button>
                                <button wire:click="deleteCondition({{ $condition->id }})" wire:confirm="Remove this record?"
                                    class="px-3 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ── SURGERIES ──────────────────────────────────────────────────────── --}}
    @if ($activeSection === 'surgeries')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">

            {{-- Section header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Surgeries & Procedures</h2>
                @if (! $surgeryShowForm)
                    <button wire:click="addSurgery"
                        class="px-4 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        + Add Surgery
                    </button>
                @endif
            </div>

            {{-- Form --}}
            @if ($surgeryShowForm)
                <form wire:submit="saveSurgery" class="bg-gray-50 rounded-xl p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Procedure <span class="text-red-400">*</span></label>
                        <textarea wire:model="surgeryProcedure" rows="2"
                            placeholder="e.g. Laparoscopic appendectomy; also combined procedures are ok"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        @error('surgeryProcedure') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Date / Year</label>
                            <input type="text" wire:model="surgeryDateYear" placeholder="e.g. 2019 or March 2019"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('surgeryDateYear') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Facility & Location</label>
                            <input type="text" wire:model="surgeryFacility" placeholder="e.g. General Hospital, NYC"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('surgeryFacility') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Surgeon / Physician</label>
                            <input type="text" wire:model="surgerySurgeon" placeholder="e.g. Dr. Williams"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('surgerySurgeon') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                        <textarea wire:model="surgeryNotes" rows="2" placeholder="Complications, recovery notes…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        @error('surgeryNotes') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                            {{ $surgeryEditingId ? 'Update' : 'Add' }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                </form>
            @endif

            {{-- List --}}
            @php $surgeries = $this->surgeries(); @endphp
            @if ($surgeries->isEmpty())
                <p class="text-sm text-gray-400">No surgeries recorded yet.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($surgeries as $surgery)
                        <div class="flex items-start justify-between gap-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ $surgery->procedure }}</p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
                                    @if ($surgery->date_year)
                                        <span class="text-xs text-gray-400">{{ $surgery->date_year }}</span>
                                    @endif
                                    @if ($surgery->facility)
                                        <span class="text-xs text-gray-400">{{ $surgery->facility }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="editSurgery({{ $surgery->id }})"
                                    class="px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50">
                                    Edit
                                </button>
                                <button wire:click="deleteSurgery({{ $surgery->id }})" wire:confirm="Remove this record?"
                                    class="px-3 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ── FAMILY HISTORY ─────────────────────────────────────────────────── --}}
    @if ($activeSection === 'family')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">

            {{-- Section header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Family History</h2>
                @if (! $familyShowForm)
                    <button wire:click="addFamily"
                        class="px-4 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        + Add Family Member
                    </button>
                @endif
            </div>

            {{-- Form --}}
            @if ($familyShowForm)
                <form wire:submit="saveFamily" class="bg-gray-50 rounded-xl p-4 space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Relative / Relationship <span class="text-red-400">*</span></label>
                            <input type="text" wire:model="familyRelative" placeholder="e.g. Mother, Father, Paternal Grandfather"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('familyRelative') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Age of Onset</label>
                            <input type="text" wire:model="familyOnset" placeholder="e.g. 65, mid-50s"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('familyOnset') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Current Status / Cause of Death</label>
                            <input type="text" wire:model="familyStatus" placeholder="e.g. Living, Deceased — heart disease"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('familyStatus') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Known Conditions <span class="text-red-400">*</span></label>
                        <textarea wire:model="familyConditions" rows="3" placeholder="e.g. Hypertension, Type 2 Diabetes, Coronary Artery Disease"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        @error('familyConditions') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                            {{ $familyEditingId ? 'Update' : 'Add' }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                </form>
            @endif

            {{-- List --}}
            @php $families = $this->familyHistories(); @endphp
            @if ($families->isEmpty())
                <p class="text-sm text-gray-400">No family history recorded yet.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($families as $fh)
                        <div class="flex items-start justify-between gap-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $fh->relative }}</p>
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $fh->conditions }}</p>
                                @if ($fh->status)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $fh->status }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="editFamily({{ $fh->id }})"
                                    class="px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50">
                                    Edit
                                </button>
                                <button wire:click="deleteFamily({{ $fh->id }})" wire:confirm="Remove this record?"
                                    class="px-3 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ── SCREENINGS ─────────────────────────────────────────────────────── --}}
    @if ($activeSection === 'screenings')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">

            {{-- Section header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Vaccines & Screenings</h2>
                @if (! $screeningShowForm)
                    <button wire:click="addScreening"
                        class="px-4 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        + Add Screening
                    </button>
                @endif
            </div>

            {{-- Form --}}
            @if ($screeningShowForm)
                <form wire:submit="saveScreening" class="bg-gray-50 rounded-xl p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Vaccine / Screening Type <span class="text-red-400">*</span></label>
                        <input type="text" wire:model="screeningType" placeholder="e.g. Colonoscopy, Flu Vaccine, Mammogram"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('screeningType') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Most Recent Date</label>
                            <input type="date" wire:model="screeningLastDate"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('screeningLastDate') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Next Due Date</label>
                            <input type="date" wire:model="screeningNextDate"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('screeningNextDate') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Provider / Facility</label>
                            <input type="text" wire:model="screeningProvider" placeholder="e.g. Dr. Jones, City Clinic"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @error('screeningProvider') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                        <textarea wire:model="screeningNotes" rows="2" placeholder="Results, reminders…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        @error('screeningNotes') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                            {{ $screeningEditingId ? 'Update' : 'Add' }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                </form>
            @endif

            {{-- List --}}
            @php
                $screenings = $this->screenings();
                $today      = now()->startOfDay();
                $soon       = now()->addDays(30)->startOfDay();
            @endphp
            @if ($screenings->isEmpty())
                <p class="text-sm text-gray-400">No screenings recorded yet.</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach ($screenings as $screening)
                        @php
                            $nextDueUrgent = $screening->next_due_date
                                && $screening->next_due_date->lessThanOrEqualTo($soon);
                        @endphp
                        <div class="flex items-start justify-between gap-4 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $screening->screening_type }}</p>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
                                    @if ($screening->last_date)
                                        <span class="text-xs text-gray-400">
                                            Last: {{ $screening->last_date->format('M j, Y') }}
                                        </span>
                                    @endif
                                    @if ($screening->next_due_date)
                                        <span @class([
                                            'text-xs font-medium',
                                            'text-red-500' => $nextDueUrgent,
                                            'text-gray-400' => ! $nextDueUrgent,
                                        ])>
                                            Due: {{ $screening->next_due_date->format('M j, Y') }}
                                        </span>
                                    @endif
                                    @if ($screening->provider)
                                        <span class="text-xs text-gray-400">{{ $screening->provider }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="editScreening({{ $screening->id }})"
                                    class="px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50">
                                    Edit
                                </button>
                                <button wire:click="deleteScreening({{ $screening->id }})" wire:confirm="Remove this record?"
                                    class="px-3 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ── MEDICATIONS ────────────────────────────────────────────────────── --}}
    @if ($activeSection === 'medications')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-5">

            {{-- Section header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Medications</h2>
                @if (! $medicationShowForm)
                    <button wire:click="addMedication"
                        class="px-4 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        + Add Medication
                    </button>
                @endif
            </div>

            {{-- Form --}}
            @if ($medicationShowForm)
                <form wire:submit="saveMedication" class="bg-gray-50 rounded-xl p-4 space-y-4">

                    {{-- Identity --}}
                    <fieldset>
                        <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Identity</legend>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Name <span class="text-red-400">*</span></label>
                                <input type="text" wire:model="medicationName" placeholder="e.g. Metformin"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @error('medicationName') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Category <span class="text-red-400">*</span></label>
                                <select wire:model="medicationCategory"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select…</option>
                                    <option>Prescription</option>
                                    <option>OTC</option>
                                    <option>Vitamin</option>
                                    <option>Supplement</option>
                                    <option>Hormone</option>
                                </select>
                                @error('medicationCategory') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Form <span class="text-red-400">*</span></label>
                                <select wire:model="medicationForm"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select…</option>
                                    <option>Tablet</option>
                                    <option>Capsule</option>
                                    <option>Liquid</option>
                                    <option>Injection</option>
                                    <option>Topical</option>
                                    <option>Powder</option>
                                    <option>Other</option>
                                </select>
                                @error('medicationForm') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Status <span class="text-red-400">*</span></label>
                                <select wire:model="medicationStatus"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option>Active</option>
                                    <option>Paused</option>
                                    <option>Discontinued</option>
                                </select>
                                @error('medicationStatus') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- Dosing --}}
                    <fieldset>
                        <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Dosing</legend>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Dosage / Strength <span class="text-red-400">*</span></label>
                                <input type="text" wire:model="medicationDosage" placeholder="e.g. 500 mg"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @error('medicationDosage') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Frequency <span class="text-red-400">*</span></label>
                                <select wire:model="medicationFrequency"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select…</option>
                                    <option>Once Daily</option>
                                    <option>Twice Daily</option>
                                    <option>Three Times Daily</option>
                                    <option>Weekly</option>
                                    <option>As Needed</option>
                                    <option>Other</option>
                                </select>
                                @error('medicationFrequency') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Timing</label>
                                <input type="text" wire:model="medicationTiming" placeholder="e.g. Morning with food"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @error('medicationTiming') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- Context --}}
                    <fieldset>
                        <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Context</legend>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Reason / Condition</label>
                                <textarea wire:model="medicationReason" rows="2" placeholder="e.g. Type 2 Diabetes management"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                                @error('medicationReason') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Prescribing Doctor</label>
                                <input type="text" wire:model="medicationDoctor" placeholder="e.g. Dr. Lee"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @error('medicationDoctor') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Start Date</label>
                                <input type="date" wire:model="medicationStartDate"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @error('medicationStartDate') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- Identification --}}
                    <fieldset>
                        <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Pill Identification</legend>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Pill Color</label>
                                <input type="text" wire:model="medicationPillColor" placeholder="e.g. White, Pink"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @error('medicationPillColor') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Pill Shape</label>
                                <select wire:model="medicationPillShape"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">Select…</option>
                                    <option>Round</option>
                                    <option>Oval</option>
                                    <option>Oblong</option>
                                    <option>Capsule</option>
                                    <option>Other</option>
                                </select>
                                @error('medicationPillShape') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </fieldset>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                        <textarea wire:model="medicationNotes" rows="2" placeholder="Side effects, special instructions…"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                        @error('medicationNotes') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                            {{ $medicationEditingId ? 'Update' : 'Add' }}
                        </button>
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                    </div>
                </form>
            @endif

            {{-- List grouped by status --}}
            @php
                $medications = $this->medications();
                $grouped     = $medications->groupBy('status');
                $statusOrder = ['Active', 'Paused', 'Discontinued'];
            @endphp
            @if ($medications->isEmpty())
                <p class="text-sm text-gray-400">No medications recorded yet.</p>
            @else
                <div class="space-y-6">
                    @foreach ($statusOrder as $statusLabel)
                        @php $group = $grouped->get($statusLabel, collect()); @endphp
                        @if ($group->isNotEmpty())
                            @php
                                $groupHeadingClass = match ($statusLabel) {
                                    'Active'       => 'text-green-700',
                                    'Paused'       => 'text-yellow-700',
                                    'Discontinued' => 'text-gray-400',
                                    default        => 'text-gray-500',
                                };
                                $statusBadgeClass = match ($statusLabel) {
                                    'Active'       => 'bg-green-100 text-green-700',
                                    'Paused'       => 'bg-yellow-100 text-yellow-700',
                                    'Discontinued' => 'bg-gray-100 text-gray-500',
                                    default        => 'bg-gray-100 text-gray-500',
                                };
                            @endphp
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide {{ $groupHeadingClass }} mb-2">
                                    {{ $statusLabel }}
                                </p>
                                <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
                                    @foreach ($group as $med)
                                        @php
                                            $catBadgeClass = match ($med->category) {
                                                'Prescription' => 'bg-indigo-100 text-indigo-700',
                                                'OTC'          => 'bg-blue-100 text-blue-700',
                                                'Vitamin'      => 'bg-amber-100 text-amber-700',
                                                'Supplement'   => 'bg-teal-100 text-teal-700',
                                                'Hormone'      => 'bg-pink-100 text-pink-700',
                                                default        => 'bg-gray-100 text-gray-600',
                                            };
                                        @endphp
                                        <div class="flex items-start justify-between gap-4 px-4 py-3 bg-white">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-medium text-gray-900">{{ $med->name }}</p>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $catBadgeClass }}">
                                                        {{ $med->category }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeClass }}">
                                                        {{ $med->status }}
                                                    </span>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">
                                                    <span class="text-xs text-gray-500">{{ $med->dosage }}</span>
                                                    <span class="text-xs text-gray-400">{{ $med->frequency }}</span>
                                                    @if ($med->timing)
                                                        <span class="text-xs text-gray-400">{{ $med->timing }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex shrink-0 gap-2">
                                                <button wire:click="lookupMedication({{ $med->id }})"
                                                    wire:loading.attr="disabled" wire:target="lookupMedication({{ $med->id }})"
                                                    class="px-3 py-1 text-xs font-medium text-teal-600 border border-teal-200 rounded-lg hover:bg-teal-50 disabled:opacity-50">
                                                    <span wire:loading.remove wire:target="lookupMedication({{ $med->id }})">Look Up</span>
                                                    <span wire:loading wire:target="lookupMedication({{ $med->id }})">…</span>
                                                </button>
                                                <button wire:click="editMedication({{ $med->id }})"
                                                    class="px-3 py-1 text-xs font-medium text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50">
                                                    Edit
                                                </button>
                                                <button wire:click="deleteMedication({{ $med->id }})" wire:confirm="Remove this record?"
                                                    class="px-3 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Lookup result panel --}}
            @if ($lookupMedName)
                <div class="mt-4 rounded-xl border border-teal-200 bg-teal-50 p-5">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div>
                            <p class="text-sm font-semibold text-teal-800">{{ $lookupMedName }}</p>
                            <p class="text-xs text-teal-600">AI-generated lookup — verify with your pharmacist or physician</p>
                        </div>
                        <button wire:click="closeLookup" class="text-teal-400 hover:text-teal-700 text-xs shrink-0">✕ Close</button>
                    </div>
                    @if ($lookupError)
                        <p class="text-sm text-red-600">{{ $lookupError }}</p>
                    @elseif ($lookupResult)
                        <div class="text-sm text-teal-900 whitespace-pre-line leading-relaxed">{{ $lookupResult }}</div>
                    @endif
                </div>
            @endif
        </div>
    @endif

</div>
