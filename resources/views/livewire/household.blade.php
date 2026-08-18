<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.guest')]
#[Title("Who's using iHealth?")]
class extends Component {
    public function mount(): void
    {
        if (Setting::get('auth_mode', 'login') !== 'household') {
            $this->redirect(route('login'));
        }
    }

    public function with(): array
    {
        return ['members' => User::has('profile')->with('profile')->orderBy('name')->get()];
    }

    public function selectUser(int $userId): void
    {
        if (Setting::get('auth_mode', 'login') !== 'household') {
            $this->redirect(route('login'));
            return;
        }

        $user = User::findOrFail($userId);

        Auth::loginUsingId($user->id);
        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function deleteUser(int $userId): void
    {
        if (Setting::get('auth_mode', 'login') !== 'household') {
            return;
        }

        User::findOrFail($userId)->delete();
    }
}; ?>

<div>
    <h2 class="mb-6 text-xl font-semibold text-neutral-900 text-center">Who's using iHealth?</h2>

    @if ($members->isEmpty())
        <div class="text-center text-sm text-gray-500 py-4 space-y-3">
            <p>No members yet.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($members as $member)
                @php
                    $initials = collect(explode(' ', $member->name))
                        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                        ->take(2)
                        ->implode('');
                @endphp
                <div class="flex items-center gap-2">
                    <button
                        wire:click="selectUser({{ $member->id }})"
                        class="flex-1 flex items-center gap-4 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-left hover:bg-indigo-50 hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors">
                        <div class="flex-shrink-0 flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-sm font-semibold select-none">
                            {{ $initials }}
                        </div>
                        <span class="text-sm font-medium text-neutral-900">{{ $member->name }}</span>
                    </button>
                    <button
                        wire:click="deleteUser({{ $member->id }})"
                        wire:confirm="Delete {{ $member->name }}? This will permanently remove all their health data."
                        class="flex-shrink-0 rounded-lg border border-neutral-200 px-3 py-3 text-neutral-400 hover:bg-red-50 hover:border-red-300 hover:text-red-500 focus:outline-none transition-colors"
                        title="Remove {{ $member->name }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-6 pt-5 border-t border-neutral-200">
        <a href="{{ route('register') }}" wire:navigate
            class="w-full flex items-center justify-center gap-2 rounded-xl border border-dashed border-neutral-300 px-4 py-3 text-sm font-medium text-neutral-500 hover:bg-neutral-50 hover:border-indigo-300 hover:text-indigo-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Add New Member
        </a>
    </div>
</div>
