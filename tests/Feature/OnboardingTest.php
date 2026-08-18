<?php

use App\Models\Profile;
use App\Models\User;

describe('onboarding middleware', function (): void {
    it('redirects an authenticated user without a profile to onboarding', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('onboarding'));
    });

    it('allows an authenticated user with a profile to reach the dashboard', function (): void {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    });

    it('does not redirect on the onboarding route itself', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('onboarding'))
            ->assertOk();
    });

    it('redirects guests from the dashboard to login', function (): void {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });

    it('redirects guests from onboarding to login', function (): void {
        $this->get(route('onboarding'))
            ->assertRedirect(route('login'));
    });
});
