<?php

use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use Livewire\Volt\Volt;

describe('household picker', function (): void {
    it('redirects to login when auth_mode is login', function (): void {
        Setting::set('auth_mode', 'login');

        $this->get(route('household'))
            ->assertRedirect(route('login'));
    });

    it('renders user cards when auth_mode is household', function (): void {
        Setting::set('auth_mode', 'household');
        $user = User::factory()->create(['name' => 'Jane Smith']);
        Profile::factory()->for($user)->create();

        $this->get(route('household'))
            ->assertOk()
            ->assertSee('Jane Smith');
    });

    it('shows empty state when no users exist', function (): void {
        Setting::set('auth_mode', 'household');

        $this->get(route('household'))
            ->assertOk()
            ->assertSee('No members yet');
    });

    it('logs in the selected user and redirects to dashboard', function (): void {
        Setting::set('auth_mode', 'household');
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        Volt::test('household')
            ->call('selectUser', $user->id);

        expect(auth()->id())->toBe($user->id);
    });

    it('redirects to login rather than showing the picker when auth_mode is login', function (): void {
        Setting::set('auth_mode', 'login');

        $this->get(route('household'))
            ->assertRedirect(route('login'));
    });

    it('unauthenticated access to dashboard redirects to household in household mode', function (): void {
        Setting::set('auth_mode', 'household');

        $this->get(route('dashboard'))
            ->assertRedirect(route('household'));
    });

    it('unauthenticated access to dashboard redirects to login in login mode', function (): void {
        Setting::set('auth_mode', 'login');

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });
});
