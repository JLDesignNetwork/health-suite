<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class extends Component {
    //
}; ?>

<div>
    <h1 class="text-2xl font-semibold tracking-tight text-neutral-900">
        Welcome, {{ auth()->user()->name }}
    </h1>
    <p class="mt-2 text-sm text-neutral-600">
        Your dashboard is empty for now. Phase 3 (onboarding wizard) and Phase 6 (daily goal rings + charts) will fill this space.
    </p>

    <div class="mt-8 rounded-lg border border-dashed border-neutral-300 bg-white p-6 text-sm text-neutral-500">
        Coming soon: baseline profile, daily entry forms, weight/BFP/pulse trends, and goal-line charts.
    </div>
</div>
