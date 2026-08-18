<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Setting;
use App\Services\AiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Kitchen')]
class extends Component {
    public string $activeTab = 'ingredients';

    // ── Ingredient form ──────────────────────────────────────────────────────
    public bool    $ingShowForm   = false;
    public ?int    $ingEditingId  = null;
    public string  $ingName       = '';
    public string  $ingQuantity   = '';
    public string  $ingUnit       = '';
    public string  $ingCategory   = '';
    public string  $ingNotes      = '';
    public bool    $ingShared     = false;
    public string  $ingQtyOnHand  = '';

    // ── Ingredient multi-select ──────────────────────────────────────────────
    public bool    $ingSelectMode  = false;
    public array   $ingSelected    = [];

    // ── Ingredient search / filter ───────────────────────────────────────────
    public string  $ingSearch         = '';
    public string  $ingFilterCategory = '';

    // ── Recipe state ─────────────────────────────────────────────────────────
    public string  $mealType        = '';
    public int     $servingsFor     = 2;
    public string  $recipeError     = '';
    public ?int    $viewingRecipeId = null;

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetIngForm();
        $this->ingSelectMode = false;
        $this->ingSelected   = [];
        $this->viewingRecipeId = null;
        $this->recipeError = '';
    }

    // ── Ingredients CRUD ─────────────────────────────────────────────────────

    public function ingredients(): \Illuminate\Support\Collection
    {
        $own = Ingredient::with('user')->orderBy('category')->orderBy('name')->get();

        if (Setting::get('auth_mode', 'login') === 'household') {
            $shared = Ingredient::withoutGlobalScopes()
                ->with('user')
                ->where('shared', true)
                ->where('user_id', '!=', auth()->id())
                ->orderBy('category')->orderBy('name')->get();

            $own = $own->merge($shared)->unique('id')->sortBy(['category', 'name'])->values();
        }

        return $own
            ->when($this->ingSearch, fn ($c) => $c->filter(
                fn ($i) => str_contains(strtolower($i->name), strtolower($this->ingSearch))
                        || str_contains(strtolower($i->notes ?? ''), strtolower($this->ingSearch))
            ))
            ->when($this->ingFilterCategory, fn ($c) => $c->filter(
                fn ($i) => $i->category === $this->ingFilterCategory
            ));
    }

    public function addIngredient(): void
    {
        $this->ingSelectMode = false;
        $this->ingSelected   = [];
        $this->resetIngForm();
        $this->ingShowForm = true;
    }

    public function toggleSelectMode(): void
    {
        $this->ingSelectMode = ! $this->ingSelectMode;
        $this->ingSelected   = [];
        if ($this->ingSelectMode) {
            $this->resetIngForm();
        }
    }

    public function selectAll(): void
    {
        $ownIds = $this->ingredients()
            ->filter(fn ($i) => $i->user_id === auth()->id())
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $this->ingSelected = count($this->ingSelected) === count($ownIds) && count($ownIds) > 0
            ? []
            : $ownIds;
    }

    public function deleteSelected(): void
    {
        Ingredient::whereIn('id', $this->ingSelected)
            ->where('user_id', auth()->id())
            ->delete();
        $this->ingSelected   = [];
        $this->ingSelectMode = false;
    }

    public function shareSelected(): void
    {
        Ingredient::whereIn('id', $this->ingSelected)
            ->where('user_id', auth()->id())
            ->update(['shared' => true]);
        $this->ingSelected = [];
    }

    public function unshareSelected(): void
    {
        Ingredient::whereIn('id', $this->ingSelected)
            ->where('user_id', auth()->id())
            ->update(['shared' => false]);
        $this->ingSelected = [];
    }

    public function editIngredient(int $id): void
    {
        $this->resetIngForm();
        $ing = Ingredient::findOrFail($id);
        $this->ingEditingId  = $ing->id;
        $this->ingName       = $ing->name;
        $this->ingQuantity   = $ing->quantity ?? '';
        $this->ingUnit       = $ing->unit ?? '';
        $this->ingCategory   = $ing->category ?? '';
        $this->ingNotes      = $ing->notes ?? '';
        $this->ingShared     = (bool) $ing->shared;
        $this->ingQtyOnHand  = (string) ($ing->quantity_on_hand ?? '');
        $this->ingShowForm   = true;
    }

    public function saveIngredient(): void
    {
        $data = $this->validate([
            'ingName'       => ['required', 'string', 'max:255'],
            'ingQuantity'   => ['nullable', 'string', 'max:50'],
            'ingUnit'       => ['nullable', 'string', 'max:50'],
            'ingCategory'   => ['nullable', 'in:Meat,Seafood,Poultry,Dairy,Eggs,Vegetable,Fruit,Grain / Pasta,Legumes / Beans,Nuts / Seeds,Herbs / Spices,Sauces / Condiments,Oils / Fats,Frozen,Canned / Packaged,Other'],
            'ingNotes'      => ['nullable', 'string', 'max:255'],
            'ingQtyOnHand'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $payload = [
            'name'             => $data['ingName'],
            'quantity'         => $data['ingQuantity'] ?: null,
            'unit'             => $data['ingUnit'] ?: null,
            'category'         => $data['ingCategory'] ?: null,
            'notes'            => $data['ingNotes'] ?: null,
            'shared'           => $this->ingShared,
            'quantity_on_hand' => $this->ingQtyOnHand !== '' ? (float) $this->ingQtyOnHand : null,
        ];

        if ($this->ingEditingId) {
            Ingredient::findOrFail($this->ingEditingId)->update($payload);
        } else {
            Ingredient::create($payload);
        }

        $this->resetIngForm();
    }

    public function deleteIngredient(int $id): void
    {
        Ingredient::findOrFail($id)->delete();
    }

    public function cancelIng(): void
    {
        $this->resetIngForm();
    }

    private function resetIngForm(): void
    {
        $this->ingShowForm  = false;
        $this->ingEditingId = null;
        $this->ingName      = '';
        $this->ingQuantity  = '';
        $this->ingUnit      = '';
        $this->ingCategory  = '';
        $this->ingNotes     = '';
        $this->ingShared    = false;
        $this->ingQtyOnHand = '';
    }

    // ── Recipes ──────────────────────────────────────────────────────────────

    public function recipes(): \Illuminate\Support\Collection
    {
        $own = Recipe::with('user')->orderBy('created_at', 'desc')->get();

        if (Setting::get('auth_mode', 'login') === 'household') {
            $others = Recipe::withoutGlobalScopes()
                ->with('user')
                ->where('user_id', '!=', auth()->id())
                ->orderBy('created_at', 'desc')->get();

            return $own->merge($others)->sortByDesc('created_at')->values();
        }

        return $own;
    }

    public function generateRecipe(): void
    {
        $this->recipeError     = '';
        $this->viewingRecipeId = null;

        $svc = app(AiService::class);

        if (! $svc->isConfigured()) {
            $this->recipeError = 'AI Assistant is not configured. Go to Settings → AI Assistant to add your API key.';
            return;
        }

        $ingredientCount = Ingredient::count();
        if ($ingredientCount === 0) {
            $this->recipeError = 'Add some ingredients to your pantry first — the AI will only use what you have on hand.';
            return;
        }

        $mealTypeStr   = $this->mealType ? "a {$this->mealType} recipe" : 'a recipe (any meal type)';
        $servingsStr   = "for {$this->servingsFor} ".($this->servingsFor === 1 ? 'person' : 'people');

        // All saved recipes
        $allRecipes  = Recipe::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get(['name', 'instructions']);
        $allNames    = $allRecipes->pluck('name')->implode(', ');
        $recent6     = $allRecipes->take(6);

        // Strip parentheticals so "Chickpeas (Ceci)" matches as "Chickpeas" in instructions
        $pantryItems = Ingredient::where('user_id', auth()->id())->pluck('name')->map(
            fn ($n) => trim(preg_replace('/\s*\(.*?\)/', '', $n))
        )->unique()->values();

        $usageCounts = $pantryItems->mapWithKeys(fn ($ing) => [
            $ing => $recent6->filter(fn ($r) => stripos($r->instructions, $ing) !== false)->count(),
        ]);

        $overused  = $usageCounts->filter(fn ($c) => $c >= 2)->keys()->values();
        $underused = $usageCounts->filter(fn ($c) => $c === 0)->keys()->values();

        $avoidNames = $allNames
            ? "Do NOT generate any of these already saved recipes: {$allNames}.\n\n"
            : '';

        $focusClause = $underused->isNotEmpty()
            ? "The following pantry ingredients have NOT been used in recent recipes — build this recipe primarily around them: "
              .$underused->shuffle()->take(5)->implode(', ').".\n\n"
            : '';

        // Overused ingredients are removed from the pantry in the system prompt — no need to mention them
        $overusedList = $overused->all();

        $prompt = "{$avoidNames}"
            ."{$focusClause}"
            ."Generate {$mealTypeStr} {$servingsStr} using ONLY the ingredients visible in my pantry above. "
            ."Do not use any ingredient not in the pantry — no substitutions, no additions.\n\n"
            ."Consider my health goals, dietary restrictions, allergies, and active conditions.\n\n"
            ."Format your response EXACTLY as follows:\n"
            ."RECIPE NAME: [name]\n"
            ."SERVINGS: {$this->servingsFor}\n"
            ."CALORIES PER SERVING: [estimated number]\n"
            ."INSTRUCTIONS:\n"
            ."[numbered step-by-step instructions]";

        try {
            $response = $svc->chatForRecipe(auth()->user(), [['role' => 'user', 'content' => $prompt]], $overusedList);
            $recipe   = $this->parseAndSaveRecipe($response);
            $this->viewingRecipeId = $recipe->id;
        } catch (\Throwable $e) {
            $this->recipeError = 'Error: '.$e->getMessage();
        }
    }

    public function deleteRecipe(int $id): void
    {
        $recipe = Recipe::findOrFail($id);
        if ($recipe->user_id !== auth()->id()) {
            return;
        }
        $recipe->delete();
        if ($this->viewingRecipeId === $id) {
            $this->viewingRecipeId = null;
        }
    }

    public function viewRecipe(int $id): void
    {
        $this->viewingRecipeId = ($this->viewingRecipeId === $id) ? null : $id;
    }

    private function parseAndSaveRecipe(string $raw): Recipe
    {
        $name       = 'Generated Recipe';
        $servings   = null;
        $calories   = null;

        if (preg_match('/RECIPE NAME:\s*(.+)/i', $raw, $m)) $name = trim($m[1]);
        if (preg_match('/SERVINGS:\s*(\d+)/i', $raw, $m))   $servings = (int) $m[1];
        if (preg_match('/CALORIES PER SERVING:\s*(\d+)/i', $raw, $m)) $calories = (int) $m[1];

        return Recipe::create([
            'name'                          => $name,
            'servings'                      => $servings,
            'estimated_calories_per_serving' => $calories,
            'instructions'                  => $raw,
        ]);
    }
}; ?>

<div class="space-y-6">
    <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Kitchen</h1>

    {{-- Tabs --}}
    <div class="flex gap-2">
        <button wire:click="switchTab('ingredients')"
            @class(['px-4 py-2 rounded-full text-sm font-medium transition-colors',
                    'bg-indigo-600 text-white' => $activeTab === 'ingredients',
                    'text-gray-600 hover:bg-gray-100' => $activeTab !== 'ingredients'])>
            Ingredients
        </button>
        <button wire:click="switchTab('recipes')"
            @class(['px-4 py-2 rounded-full text-sm font-medium transition-colors',
                    'bg-indigo-600 text-white' => $activeTab === 'recipes',
                    'text-gray-600 hover:bg-gray-100' => $activeTab !== 'recipes'])>
            Recipes
        </button>
    </div>

    {{-- ── Ingredients tab ── --}}
    @if ($activeTab === 'ingredients')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Pantry / Ingredients</h2>
                    <p class="text-xs text-gray-400 mt-0.5">The AI uses only these ingredients when generating recipes.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if (! $ingSelectMode)
                        <button wire:click="toggleSelectMode"
                            class="px-3 py-1.5 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Select
                        </button>
                        <button wire:click="addIngredient"
                            class="px-3 py-1.5 text-sm font-medium text-indigo-600 border border-indigo-300 rounded-lg hover:bg-indigo-50">
                            + Add
                        </button>
                    @else
                        <button wire:click="selectAll"
                            class="px-3 py-1.5 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Select All
                        </button>
                        <span class="text-sm text-gray-500">{{ count($ingSelected) }} selected</span>
                        <button wire:click="toggleSelectMode"
                            class="px-3 py-1.5 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                    @endif
                </div>
            </div>

            {{-- Add form (top) — only for new ingredients --}}
            @if ($ingShowForm && ! $ingEditingId)
                <form wire:submit="saveIngredient" class="bg-gray-50 rounded-xl p-4 mb-5 space-y-3">
                    @include('livewire.partials.ingredient-form-fields')
                </form>
            @endif

            {{-- Bulk action bar — always visible in select mode --}}
            @if ($ingSelectMode)
                @php $selCount = count($ingSelected); @endphp
                <div class="flex flex-wrap items-center gap-2 mb-4 px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl">
                    <span class="text-sm text-gray-500 flex-1">
                        {{ $selCount > 0 ? $selCount.' selected' : 'Check items to act on them' }}
                    </span>
                    @if (\App\Models\Setting::get('auth_mode', 'login') === 'household')
                        <button wire:click="shareSelected" @disabled($selCount === 0)
                            @class(['px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors',
                                    'text-purple-700 border-purple-300 bg-white hover:bg-purple-50' => $selCount > 0,
                                    'text-gray-300 border-gray-200 bg-white cursor-not-allowed'     => $selCount === 0])>
                            Share
                        </button>
                        <button wire:click="unshareSelected" @disabled($selCount === 0)
                            @class(['px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors',
                                    'text-gray-600 border-gray-300 bg-white hover:bg-gray-100' => $selCount > 0,
                                    'text-gray-300 border-gray-200 bg-white cursor-not-allowed' => $selCount === 0])>
                            Unshare
                        </button>
                    @endif
                    <button wire:click="deleteSelected"
                        @if ($selCount > 0) wire:confirm="Delete selected ingredients?" @endif
                        @disabled($selCount === 0)
                        @class(['px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors',
                                'text-red-600 border-red-300 bg-white hover:bg-red-50'          => $selCount > 0,
                                'text-gray-300 border-gray-200 bg-white cursor-not-allowed'      => $selCount === 0])>
                        Delete{{ $selCount > 0 ? ' '.$selCount.' item'.($selCount !== 1 ? 's' : '') : '' }}
                    </button>
                </div>
            @endif

            {{-- Search & filter bar --}}
            <div class="flex flex-wrap gap-3 mb-4">
                <input type="text" wire:model.live="ingSearch" placeholder="Search ingredients…"
                    class="flex-1 min-w-48 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <select wire:model.live="ingFilterCategory"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All categories</option>
                    @foreach (['Meat','Seafood','Poultry','Dairy','Eggs','Vegetable','Fruit','Grain / Pasta','Legumes / Beans','Nuts / Seeds','Herbs / Spices','Sauces / Condiments','Oils / Fats','Frozen','Canned / Packaged','Other'] as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            @php $ingredients = $this->ingredients(); @endphp
            @if ($ingredients->isEmpty())
                <p class="text-sm text-gray-400">No ingredients yet — add what you have in your pantry or fridge.</p>
            @else
                @php $grouped = $ingredients->groupBy(fn ($i) => $i->category ?: 'Uncategorised'); @endphp
                <div class="space-y-4">
                    @foreach ($grouped as $cat => $items)
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{{ $cat }}</p>
                            <ul class="divide-y divide-gray-100">
                                @foreach ($items as $ing)
                                    @php $isOwn = $ing->user_id === auth()->id(); @endphp
                                    <li class="flex items-center justify-between py-2 gap-3 @if($ingEditingId === $ing->id) bg-indigo-50/40 @endif">
                                        @if ($ingSelectMode && $isOwn)
                                            <input type="checkbox" wire:model="ingSelected" value="{{ $ing->id }}"
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 shrink-0">
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <span class="text-sm font-medium text-gray-900">{{ $ing->name }}</span>
                                            @if ($ing->quantity)
                                                <span class="ml-2 text-xs text-gray-400">{{ $ing->quantity }}</span>
                                            @endif
                                            @if ($ing->notes)
                                                <span class="ml-2 text-xs text-gray-400 italic">{{ $ing->notes }}</span>
                                            @endif
                                            @if ($ing->quantity_on_hand !== null)
                                                @if ($ing->quantity_on_hand <= 0)
                                                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Depleted</span>
                                                @elseif ($ing->quantity_on_hand < 1)
                                                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">Low ({{ $ing->quantity_on_hand }})</span>
                                                @else
                                                    <span class="ml-2 text-xs text-gray-400">{{ $ing->quantity_on_hand }} in stock</span>
                                                @endif
                                            @endif
                                            @if ($ing->shared)
                                                <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">Shared</span>
                                            @endif
                                            @if (! $isOwn)
                                                <span class="ml-2 text-xs text-gray-400">From: {{ $ing->user?->name }}</span>
                                            @endif
                                        </div>
                                        @if ($isOwn && ! $ingSelectMode)
                                        <div class="flex gap-2 shrink-0">
                                            <button wire:click="editIngredient({{ $ing->id }})"
                                                class="text-xs text-indigo-500 hover:text-indigo-700">Edit</button>
                                            <button wire:click="deleteIngredient({{ $ing->id }})" wire:confirm="Remove {{ $ing->name }}?"
                                                class="text-xs text-red-400 hover:text-red-600">Delete</button>
                                        </div>
                                        @endif
                                    </li>
                                    {{-- Inline edit form — renders directly after the row being edited --}}
                                    @if ($ingEditingId === $ing->id && $ingShowForm)
                                        <li x-data x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })" class="py-3">
                                            <form wire:submit="saveIngredient" class="bg-gray-50 rounded-xl p-4 space-y-3">
                                                @include('livewire.partials.ingredient-form-fields')
                                            </form>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ── Recipes tab ── --}}
    @if ($activeTab === 'recipes')
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Recipes</h2>
                    <p class="text-xs text-gray-400 mt-0.5">AI-generated using only your pantry ingredients, tailored to your health profile.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model="mealType"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Any meal type</option>
                        @foreach (['Breakfast','Brunch','Lunch','Dinner','Snack','Dessert','Appetizer','Side Dish'] as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-1.5">
                        <label class="text-xs text-gray-500 whitespace-nowrap">for</label>
                        <select wire:model="servingsFor"
                            class="rounded-lg border border-gray-300 px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach (range(1, 12) as $n)
                                <option value="{{ $n }}">{{ $n }} {{ $n === 1 ? 'person' : 'people' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button wire:click="generateRecipe"
                        wire:loading.attr="disabled" wire:target="generateRecipe"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        <span wire:loading.remove wire:target="generateRecipe">Generate Recipe</span>
                        <span wire:loading wire:target="generateRecipe">Generating…</span>
                    </button>
                </div>
            </div>

            @if ($recipeError)
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {{ $recipeError }}
                </div>
            @endif

            @php $recipes = $this->recipes(); @endphp
            @if ($recipes->isEmpty())
                <p class="text-sm text-gray-400">No recipes saved yet. Add your ingredients then hit "Generate Recipe".</p>
            @else
                <div class="space-y-3">
                    @foreach ($recipes as $recipe)
                        @php $isOwnRecipe = $recipe->user_id === auth()->id(); @endphp
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $recipe->name }}</p>
                                    <div class="flex gap-3 text-xs text-gray-400 mt-0.5">
                                        @if ($recipe->servings) <span>{{ $recipe->servings }} servings</span> @endif
                                        @if ($recipe->estimated_calories_per_serving) <span>{{ number_format($recipe->estimated_calories_per_serving) }} kcal/serving</span> @endif
                                        <span>{{ $recipe->created_at->format('M j, Y') }}</span>
                                        @if (! $isOwnRecipe)
                                            <span class="text-gray-400">by {{ $recipe->user?->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-2 shrink-0">
                                    <button wire:click="viewRecipe({{ $recipe->id }})"
                                        class="text-xs text-indigo-500 hover:text-indigo-700">
                                        {{ $viewingRecipeId === $recipe->id ? 'Hide' : 'View' }}
                                    </button>
                                    @if ($isOwnRecipe)
                                    <button wire:click="deleteRecipe({{ $recipe->id }})" wire:confirm="Delete this recipe?"
                                        class="text-xs text-red-400 hover:text-red-600">Delete</button>
                                    @endif
                                </div>
                            </div>
                            @if ($viewingRecipeId === $recipe->id)
                                <div class="px-4 py-4 text-sm text-gray-800 whitespace-pre-line leading-relaxed border-t border-gray-100">
                                    {{ $recipe->instructions }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
