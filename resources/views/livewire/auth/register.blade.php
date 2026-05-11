<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new
#[Layout('components.layouts.guest')]
#[Title('Create your account')]
class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|email|max:255|unique:users,email')]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $this->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<div>
    <h2 class="mb-6 text-xl font-semibold text-neutral-900">Create your account</h2>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-neutral-700">Name</label>
            <input
                wire:model="name"
                type="text"
                id="name"
                required
                autofocus
                autocomplete="name"
                class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm shadow-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
            >
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-neutral-700">Email</label>
            <input
                wire:model="email"
                type="email"
                id="email"
                required
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
                autocomplete="new-password"
                class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm shadow-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
            >
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-neutral-700">Confirm password</label>
            <input
                wire:model="password_confirmation"
                type="password"
                id="password_confirmation"
                required
                autocomplete="new-password"
                class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm shadow-sm focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-md bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2"
        >
            Create account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-neutral-600">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-neutral-900 hover:underline">Sign in</a>
    </p>
</div>
