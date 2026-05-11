<?php

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.guest')]
#[Title('Sign in')]
class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            $this->addError('email', __('auth.failed'));

            return;
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirectIntended(default: route('dashboard'), navigate: true);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), maxAttempts: 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->addError('email', __('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]));
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div>
    <h2 class="mb-6 text-xl font-semibold text-neutral-900">Sign in</h2>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-neutral-700">Email</label>
            <input
                wire:model="email"
                type="email"
                id="email"
                required
                autofocus
                autocomplete="username"
                class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm shadow-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
            >
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-neutral-700">Password</label>
            <input
                wire:model="password"
                type="password"
                id="password"
                required
                autocomplete="current-password"
                class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm shadow-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
            >
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-neutral-700">
            <input wire:model="remember" type="checkbox" class="rounded border-neutral-300">
            Remember me
        </label>

        <button
            type="submit"
            class="w-full rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2"
        >
            Sign in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-neutral-600">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-neutral-900 hover:underline">Create one</a>
    </p>
</div>
