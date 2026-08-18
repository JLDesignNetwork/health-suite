<?php

use App\Enums\MealType;
use App\Models\Meal;
use App\Models\MealIngredient;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiService;
use Livewire\Volt\Component;

new class extends Component {
    public string $date = '';

    // Personal add/edit form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $meal_type = '';
    public string $description = '';
    public string $calories = '';
    public string $calorieEstimateError = '';
    public string $selectedRecipeId = '';

    // Ingredient usage tracking
    public array $usedIngredientIds = [];   // ingredient IDs checked by user
    public array $usedAmounts       = [];   // ['ingredientId' => 'amount string']

    // Household meal form
    public bool $showHouseholdForm = false;
    public array $householdUserIds = [];

    public function mount(string $date): void
    {
        $this->date = $date;
    }

    public function meals(): \Illuminate\Support\Collection
    {
        return Meal::whereDate('date', $this->date)
            ->orderByRaw("CASE meal_type WHEN 'breakfast' THEN 1 WHEN 'lunch' THEN 2 WHEN 'dinner' THEN 3 ELSE 4 END")
            ->get();
    }

    public function householdMembers(): \Illuminate\Support\Collection
    {
        if (Setting::get('auth_mode', 'login') !== 'household') {
            return collect();
        }

        return User::whereHas('profile', fn ($q) => $q->withoutGlobalScopes())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function pantryIngredients(): \Illuminate\Support\Collection
    {
        return \App\Models\Ingredient::orderBy('category')->orderBy('name')->get(['id', 'name', 'quantity', 'quantity_on_hand', 'category']);
    }

    public function savedRecipes(): \Illuminate\Support\Collection
    {
        $own = \App\Models\Recipe::orderBy('name')->get(['id', 'name', 'estimated_calories_per_serving', 'servings']);

        if (Setting::get('auth_mode', 'login') === 'household') {
            $shared = \App\Models\Recipe::withoutGlobalScopes()
                ->where('user_id', '!=', auth()->id())
                ->orderBy('name')
                ->get(['id', 'name', 'estimated_calories_per_serving', 'servings']);
            return $own->merge($shared)->sortBy('name')->values();
        }

        return $own;
    }

    public function updatedSelectedRecipeId(): void
    {
        if (! $this->selectedRecipeId) {
            return;
        }
        $recipe = \App\Models\Recipe::withoutGlobalScopes()->find($this->selectedRecipeId);
        if (! $recipe) return;

        $this->description = $recipe->name;
        if ($recipe->estimated_calories_per_serving) {
            $this->calories = (string) $recipe->estimated_calories_per_serving;
        }
    }

    public function addMeal(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editMeal(int $id): void
    {
        $meal = Meal::find($id);
        if (! $meal) return;

        $this->editingId   = $id;
        $this->meal_type   = $meal->meal_type->value;
        $this->description = $meal->description;
        $this->calories    = (string) $meal->calories;
        $this->showForm    = true;

        // Pre-populate ingredient usage from existing meal_ingredients
        $this->usedIngredientIds = [];
        $this->usedAmounts       = [];
        $mealIngredients = \App\Models\MealIngredient::where('meal_id', $id)->get();
        foreach ($mealIngredients as $mi) {
            $this->usedIngredientIds[] = (string) $mi->ingredient_id;
            if ($mi->amount_used !== null) {
                $this->usedAmounts[(string) $mi->ingredient_id] = (string) $mi->amount_used;
            }
        }
    }

    public function estimateCalories(): void
    {
        $this->calorieEstimateError = '';

        if (! filled($this->description)) {
            $this->calorieEstimateError = 'Enter a description first.';
            return;
        }

        $svc = app(AiService::class);
        if (! $svc->isConfigured()) {
            $this->calorieEstimateError = 'AI not configured — add your API key in Settings.';
            return;
        }

        try {
            $mealContext = $this->meal_type ? "({$this->meal_type}) " : '';
            $prompt = "Estimate the total calories for this meal: {$mealContext}{$this->description}. "
                ."Reply with ONLY a single integer number representing the total calories. No units, no explanation.";

            $response = $svc->chat(auth()->user(), [['role' => 'user', 'content' => $prompt]]);
            $estimated = (int) preg_replace('/\D/', '', $response);

            if ($estimated > 0) {
                $this->calories = (string) $estimated;
            } else {
                $this->calorieEstimateError = 'Could not parse a calorie estimate from the AI response.';
            }
        } catch (\Throwable $e) {
            $this->calorieEstimateError = 'Error: '.$e->getMessage();
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'meal_type'   => ['required', 'in:'.implode(',', array_column(MealType::cases(), 'value'))],
            'description' => ['required', 'string', 'max:255'],
            'calories'    => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        if ($this->editingId) {
            Meal::find($this->editingId)?->update($data);
            $mealId = $this->editingId;
        } else {
            $meal   = Meal::create(['date' => $this->date, ...$data]);
            $mealId = $meal->id;
        }

        // Restore previous ingredient quantities if editing
        if ($this->editingId) {
            $existing = \App\Models\MealIngredient::where('meal_id', $mealId)->with('ingredient')->get();
            foreach ($existing as $mi) {
                if ($mi->ingredient && $mi->ingredient->quantity_on_hand !== null && $mi->amount_used !== null) {
                    \App\Models\Ingredient::withoutGlobalScopes()
                        ->where('id', $mi->ingredient_id)->increment('quantity_on_hand', $mi->amount_used);
                }
                $mi->delete();
            }
        }

        // Apply new ingredient usages
        foreach ($this->usedIngredientIds as $ingId) {
            $amount = isset($this->usedAmounts[$ingId]) && $this->usedAmounts[$ingId] !== '' ? (float) $this->usedAmounts[$ingId] : null;
            \App\Models\MealIngredient::create([
                'meal_id'       => $mealId,
                'ingredient_id' => (int) $ingId,
                'amount_used'   => $amount,
            ]);
            if ($amount !== null && $amount > 0) {
                \App\Models\Ingredient::withoutGlobalScopes()
                    ->where('id', $ingId)->whereNotNull('quantity_on_hand')
                    ->decrement('quantity_on_hand', $amount);
            }
        }

        $this->resetForm();
        $this->dispatch('dashboard-refresh');
    }

    public function deleteMeal(int $id): void
    {
        $mealIngredients = \App\Models\MealIngredient::where('meal_id', $id)->with('ingredient')->get();
        foreach ($mealIngredients as $mi) {
            if ($mi->ingredient && $mi->ingredient->quantity_on_hand !== null && $mi->amount_used !== null && $mi->amount_used > 0) {
                \App\Models\Ingredient::withoutGlobalScopes()
                    ->where('id', $mi->ingredient_id)->increment('quantity_on_hand', $mi->amount_used);
            }
        }
        Meal::find($id)?->delete();
        $this->dispatch('dashboard-refresh');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function addHouseholdMeal(): void
    {
        $this->resetForm();
        $this->showHouseholdForm = true;
        $this->householdUserIds  = array_filter([auth()->id()]);
    }

    public function saveHouseholdMeal(): void
    {
        $this->validate([
            'meal_type'          => ['required', 'in:'.implode(',', array_column(MealType::cases(), 'value'))],
            'description'        => ['required', 'string', 'max:255'],
            'calories'           => ['required', 'integer', 'min:0', 'max:10000'],
            'householdUserIds'   => ['required', 'array', 'min:1'],
            'householdUserIds.*' => ['integer', 'exists:users,id'],
        ]);

        foreach ($this->householdUserIds as $userId) {
            Meal::create([
                'user_id'     => (int) $userId,
                'date'        => $this->date,
                'meal_type'   => $this->meal_type,
                'description' => $this->description,
                'calories'    => $this->calories,
            ]);
        }

        $this->resetForm();
        $this->dispatch('dashboard-refresh');
    }

    private function resetForm(): void
    {
        $this->showForm               = false;
        $this->showHouseholdForm      = false;
        $this->editingId              = null;
        $this->meal_type              = '';
        $this->description            = '';
        $this->calories               = '';
        $this->calorieEstimateError   = '';
        $this->selectedRecipeId       = '';
        $this->householdUserIds       = [];
        $this->usedIngredientIds      = [];
        $this->usedAmounts            = [];
    }
}; ?>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
    @php
        $householdMembers = $this->householdMembers();
        $isHouseholdMode  = $householdMembers->isNotEmpty();
    @endphp

    <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-semibold">Meals</h2>
        <div class="flex items-center gap-2">
            @if ($isHouseholdMode)
                <button wire:click="addHouseholdMeal"
                    class="px-3 py-1.5 text-sm font-medium text-purple-600 border border-purple-300 rounded-lg hover:bg-purple-50">
                    + Household Meal
                </button>
            @endif
            <button wire:click="addMeal"
                class="px-3 py-1.5 text-sm font-medium text-indigo-600 border border-indigo-300 rounded-lg hover:bg-indigo-50">
                + Add Meal
            </button>
        </div>
    </div>

    {{-- Personal meal form --}}
    @if ($showForm)
        <form wire:submit="save" class="bg-gray-50 rounded-xl p-4 mb-5 space-y-3">
            {{-- Recipe picker --}}
            @php $recipes = $this->savedRecipes(); @endphp
            @if ($recipes->isNotEmpty())
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From a saved recipe <span class="text-gray-400">(optional — auto-fills description & calories)</span></label>
                    <select wire:model.live="selectedRecipeId"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">— or type a custom meal below —</option>
                        @foreach ($recipes as $recipe)
                            <option value="{{ $recipe->id }}">
                                {{ $recipe->name }}{{ $recipe->estimated_calories_per_serving ? ' · '.$recipe->estimated_calories_per_serving.' kcal/serving' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="border-t border-gray-200"></div>
            @endif
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                    <select wire:model="meal_type"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select…</option>
                        @foreach (\App\Enums\MealType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('meal_type') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">
                        Calories (kcal)
                        <button type="button" wire:click="estimateCalories"
                            wire:loading.attr="disabled" wire:target="estimateCalories"
                            class="ml-1 text-xs font-normal text-indigo-500 hover:text-indigo-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="estimateCalories">✦ Estimate</span>
                            <span wire:loading wire:target="estimateCalories">Estimating…</span>
                        </button>
                    </label>
                    <input type="number" wire:model="calories" min="0" placeholder="optional"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @if ($calorieEstimateError) <p class="text-xs text-red-500 mt-0.5">{{ $calorieEstimateError }}</p> @endif
                    @error('calories') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Description</label>
                <input type="text" wire:model="description" placeholder="e.g. Oatmeal with banana"
                    class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('description') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
            </div>

            {{-- Ingredient usage tracking --}}
            @php $pantry = $this->pantryIngredients(); @endphp
            @if ($pantry->isNotEmpty())
                <div>
                    <label class="block text-xs text-gray-500 mb-2">Ingredients Used <span class="text-gray-400 font-normal">(optional — reduces pantry stock)</span></label>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto border border-gray-200 rounded-lg p-2">
                        @foreach ($pantry as $ing)
                            <label class="flex items-center gap-2 cursor-pointer text-sm">
                                <input type="checkbox"
                                    wire:model="usedIngredientIds"
                                    value="{{ $ing->id }}"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="flex-1 text-gray-800">{{ $ing->name }}</span>
                                @if ($ing->quantity_on_hand !== null)
                                    <span class="text-xs text-gray-400">{{ $ing->quantity_on_hand }} left</span>
                                @endif
                                @if (in_array((string) $ing->id, array_map('strval', $usedIngredientIds)))
                                    <input type="number" step="0.1" min="0"
                                        wire:model="usedAmounts.{{ $ing->id }}"
                                        placeholder="qty"
                                        class="w-16 rounded border border-gray-300 px-1.5 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="px-4 py-1.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    {{ $editingId ? 'Update' : 'Add' }}
                </button>
                <button type="button" wire:click="cancel"
                    class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100">
                    Cancel
                </button>
            </div>
        </form>
    @endif

    {{-- Household meal form --}}
    @if ($showHouseholdForm)
        <form wire:submit="saveHouseholdMeal" class="bg-purple-50 rounded-xl p-4 mb-5 space-y-3 border border-purple-100">
            <p class="text-xs font-semibold text-purple-700 uppercase tracking-wide">Household Meal</p>
            {{-- Recipe picker --}}
            @if ($this->savedRecipes()->isNotEmpty())
                <div>
                    <label class="block text-xs text-gray-500 mb-1">From a saved recipe <span class="text-gray-400">(optional)</span></label>
                    <select wire:model.live="selectedRecipeId"
                        class="w-full rounded-lg border border-purple-200 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">— or type a custom meal below —</option>
                        @foreach ($this->savedRecipes() as $recipe)
                            <option value="{{ $recipe->id }}">
                                {{ $recipe->name }}{{ $recipe->estimated_calories_per_serving ? ' · '.$recipe->estimated_calories_per_serving.' kcal/serving' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="border-t border-purple-200"></div>
            @endif
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Type</label>
                    <select wire:model="meal_type"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Select…</option>
                        @foreach (\App\Enums\MealType::cases() as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @error('meal_type') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Calories (kcal)</label>
                    <input type="number" wire:model="calories" min="0"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                    @error('calories') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Description</label>
                <input type="text" wire:model="description" placeholder="e.g. Family pasta dinner"
                    class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                @error('description') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-2">Who ate this?</label>
                <div class="space-y-1.5">
                    @foreach ($householdMembers as $member)
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox"
                                wire:model="householdUserIds"
                                value="{{ $member->id }}"
                                class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span class="text-gray-800">{{ $member->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('householdUserIds') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="px-4 py-1.5 text-sm font-semibold text-white bg-purple-600 rounded-lg hover:bg-purple-700">
                    Add for Selected
                </button>
                <button type="button" wire:click="cancel"
                    class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100">
                    Cancel
                </button>
            </div>
        </form>
    @endif

    @php $meals = $this->meals(); @endphp

    @if ($meals->isEmpty())
        <p class="text-sm text-gray-400">No meals logged for this date.</p>
    @else
        @php $totalCal = $meals->sum('calories'); @endphp
        <ul class="divide-y divide-gray-100 mb-3">
            @foreach ($meals as $meal)
                <li class="flex items-center justify-between py-2.5 gap-3">
                    <div class="min-w-0">
                        <span class="inline-block text-xs font-medium text-indigo-600 bg-indigo-50 rounded px-1.5 py-0.5 mr-2">
                            {{ $meal->meal_type->label() }}
                        </span>
                        <span class="text-sm text-gray-800">{{ $meal->description }}</span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-sm font-medium text-gray-600">{{ number_format($meal->calories ?? 0) }} kcal</span>
                        <button wire:click="editMeal({{ $meal->id }})"
                            class="text-xs text-indigo-500 hover:text-indigo-700">Edit</button>
                        <button wire:click="deleteMeal({{ $meal->id }})" wire:confirm="Remove this meal?"
                            class="text-xs text-red-400 hover:text-red-600">Delete</button>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="text-sm font-semibold text-right text-gray-700">Total: {{ number_format($totalCal) }} kcal</p>
    @endif
</div>
