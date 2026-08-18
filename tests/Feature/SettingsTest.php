<?php

use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Profile::factory()->for($this->user)->create();
    $this->actingAs($this->user);
});

describe('settings page', function (): void {
    it('renders for an authenticated user', function (): void {
        $this->get(route('settings'))->assertOk();
    });

    it('redirects guests to login in login mode', function (): void {
        auth()->logout();

        $this->get(route('settings'))
            ->assertRedirect(route('login'));
    });

    it('redirects guests to household in household mode', function (): void {
        Setting::set('auth_mode', 'household');
        auth()->logout();

        $this->get(route('settings'))
            ->assertRedirect(route('household'));
    });

    it('shows the current auth_mode on mount', function (): void {
        Setting::set('auth_mode', 'household');

        $this->get(route('settings'))
            ->assertOk()
            ->assertSee('household');
    });

    it('defaults to login mode when no setting exists', function (): void {
        $this->get(route('settings'))
            ->assertOk()
            ->assertSee('Login Mode');
    });

    it('persists auth_mode to the settings table', function (): void {
        Volt::test('settings')
            ->set('authMode', 'household')
            ->call('save');

        expect(Setting::get('auth_mode'))->toBe('household');
    });

    it('logout redirects to household when household mode is active', function (): void {
        Setting::set('auth_mode', 'household');

        $this->post(route('logout'))
            ->assertRedirect(route('household'));
    });

    it('logout redirects to login when login mode is active', function (): void {
        Setting::set('auth_mode', 'login');

        $this->post(route('logout'))
            ->assertRedirect(route('login'));
    });
});
