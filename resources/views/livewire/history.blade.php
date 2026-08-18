<?php

use App\Models\HealthRecord;
use App\Models\Meal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new
#[Layout('components.layouts.app')]
#[Title('History')]
class extends Component {
    use WithPagination;

    #[Url(as: 'per_page')]
    public int $perPage = 25;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $records = HealthRecord::orderBy('date', 'desc')
            ->paginate($this->perPage);

        // Pre-load all meals for the fetched dates so we avoid N+1
        $dates = $records->pluck('date')->map(fn ($d) => $d->toDateString())->all();

        $meals = Meal::where(function ($q) use ($dates): void {
                foreach ($dates as $date) {
                    $q->orWhereDate('date', $date);
                }
            })
            ->orderByRaw("CASE meal_type WHEN 'breakfast' THEN 1 WHEN 'lunch' THEN 2 WHEN 'dinner' THEN 3 ELSE 4 END")
            ->get()
            ->groupBy(fn ($m) => $m->date->toDateString())
            ->map(fn ($group) => $group->values()->all())
            ->all();

        return compact('records', 'meals');
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">History</h1>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 text-sm">
                <label for="perPage" class="text-gray-500">Show</label>
                <select id="perPage" wire:model.live="perPage"
                    class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <a href="{{ route('history.export') }}"
                class="px-4 py-1.5 text-sm font-medium text-indigo-600 border border-indigo-300 rounded-lg hover:bg-indigo-50">
                Export CSV
            </a>
        </div>
    </div>

    @if ($records->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-400">
            No health records yet. <a href="{{ route('dashboard') }}" class="text-indigo-600 underline">Add one from the dashboard.</a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Date</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">Weight</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">Neck</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">Waist</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">Hip</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">SYS</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">DIA</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">Pulse</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">Water</th>
                            <th class="px-3 py-3 text-right font-medium text-gray-500">Exercise</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Meals</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($records as $record)
                            @php
                                $date     = $record->date->toDateString();
                                $dayMeals = $meals[$date] ?? [];
                                $rowCount = max(1, count($dayMeals));
                            @endphp

                            @for ($i = 0; $i < $rowCount; $i++)
                                @php $meal = $dayMeals[$i] ?? null; @endphp
                                <tr class="hover:bg-gray-50">
                                    {{-- Date: only shown on first sub-row --}}
                                    <td @class(['px-4 py-2.5 whitespace-nowrap text-gray-500', 'invisible' => $i > 0])>
                                        {{ $record->date->format('M j, Y') }}
                                    </td>
                                    {{-- Health record columns: only on first sub-row --}}
                                    @if ($i === 0)
                                        <td class="px-3 py-2.5 text-right text-gray-800">{{ $record->weight ? $record->weight.' kg' : '—' }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-500">{{ $record->neck ? $record->neck.' cm' : '—' }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-500">{{ $record->waist ? $record->waist.' cm' : '—' }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-500">{{ $record->hip ? $record->hip.' cm' : '—' }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-500">{{ $record->systolic ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-500">{{ $record->diastolic ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-500">{{ $record->pulse ?? '—' }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-500">{{ $record->water_intake_l ? $record->water_intake_l.' L' : '—' }}</td>
                                        <td class="px-3 py-2.5 text-right text-gray-500">{{ $record->exercise_minutes ? $record->exercise_minutes.' min' : '—' }}</td>
                                    @else
                                        <td colspan="9"></td>
                                    @endif
                                    {{-- Meal column --}}
                                    <td class="px-4 py-2.5">
                                        @if ($meal)
                                            <span class="inline-block text-xs font-medium text-indigo-600 bg-indigo-50 rounded px-1.5 py-0.5 mr-1">
                                                {{ $meal->meal_type->label() }}
                                            </span>
                                            <span class="text-gray-700">{{ $meal->description }}</span>
                                            <span class="text-gray-400 ml-1">{{ number_format($meal->calories) }} kcal</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($records->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
