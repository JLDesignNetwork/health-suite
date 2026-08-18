<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Name <span class="text-red-500">*</span></label>
        <input type="text" wire:model="ingName" autofocus
            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @error('ingName') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Unit size <span class="text-gray-400 font-normal">(one package / container)</span></label>
        <input type="text" wire:model="ingQuantity"
            placeholder="e.g. 1kg pkg, 1 can (400g), 750ml bottle"
            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
</div>
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Qty on hand <span class="text-gray-400">(how many of the above unit)</span></label>
        <input type="number" step="0.1" min="0" wire:model="ingQtyOnHand" placeholder="e.g. 3"
            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Category</label>
        <select wire:model="ingCategory"
            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">— none —</option>
            @foreach (['Meat','Seafood','Poultry','Dairy','Eggs','Vegetable','Fruit','Grain / Pasta','Legumes / Beans','Nuts / Seeds','Herbs / Spices','Sauces / Condiments','Oils / Fats','Frozen','Canned / Packaged','Other'] as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>
    </div>
</div>
<div>
    <label class="block text-xs text-gray-500 mb-1">Notes</label>
    <input type="text" wire:model="ingNotes" placeholder="optional"
        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
</div>
@if (\App\Models\Setting::get('auth_mode', 'login') === 'household')
<div class="flex items-center gap-2">
    <input type="checkbox" wire:model="ingShared" id="ingShared" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
    <label for="ingShared" class="text-xs text-gray-600">Share with household members</label>
</div>
@endif
<div class="flex gap-3 pt-1">
    <button type="submit"
        class="px-4 py-1.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
        {{ $ingEditingId ? 'Update' : 'Add' }}
    </button>
    <button type="button" wire:click="cancelIng"
        class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-100">
        Cancel
    </button>
</div>
