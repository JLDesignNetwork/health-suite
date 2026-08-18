<?php

use App\Models\HealthRecord;
use App\Models\Meal;
use App\Models\Profile;
use App\Models\User;

beforeEach(function (): void {
    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();
});

describe('global scope isolation', function (): void {
    it('returns only the authenticated user\'s health records', function (): void {
        HealthRecord::factory()->count(3)->for($this->alice)->create();
        HealthRecord::factory()->count(5)->for($this->bob)->create();

        $this->actingAs($this->alice);

        expect(HealthRecord::count())->toBe(3)
            ->and(HealthRecord::pluck('user_id')->unique()->sole())->toBe($this->alice->id);
    });

    it('returns only the authenticated user\'s meals', function (): void {
        Meal::factory()->count(4)->for($this->alice)->create();
        Meal::factory()->count(7)->for($this->bob)->create();

        $this->actingAs($this->alice);

        expect(Meal::count())->toBe(4)
            ->and(Meal::pluck('user_id')->unique()->sole())->toBe($this->alice->id);
    });

    it('returns only the authenticated user\'s profile', function (): void {
        Profile::factory()->for($this->alice)->create();
        Profile::factory()->for($this->bob)->create();

        $this->actingAs($this->alice);

        expect(Profile::count())->toBe(1)
            ->and(Profile::first()->user_id)->toBe($this->alice->id);
    });

    it('auto-fills user_id from the authenticated user on create', function (): void {
        $this->actingAs($this->alice);

        $record = HealthRecord::factory()->make(['user_id' => null]);
        $record->save();

        expect($record->user_id)->toBe($this->alice->id);
    });
});

describe('policies', function (): void {
    it('allows a user to view their own health record', function (): void {
        $record = HealthRecord::factory()->for($this->alice)->create();

        expect($this->alice->can('view', $record))->toBeTrue()
            ->and($this->bob->can('view', $record))->toBeFalse();
    });

    it('allows a user to update their own health record', function (): void {
        $record = HealthRecord::factory()->for($this->alice)->create();

        expect($this->alice->can('update', $record))->toBeTrue()
            ->and($this->bob->can('update', $record))->toBeFalse();
    });

    it('allows a user to delete their own health record', function (): void {
        $record = HealthRecord::factory()->for($this->alice)->create();

        expect($this->alice->can('delete', $record))->toBeTrue()
            ->and($this->bob->can('delete', $record))->toBeFalse();
    });

    it('allows a user to view their own meal', function (): void {
        $meal = Meal::factory()->for($this->alice)->create();

        expect($this->alice->can('view', $meal))->toBeTrue()
            ->and($this->bob->can('view', $meal))->toBeFalse();
    });

    it('allows a user to update their own meal', function (): void {
        $meal = Meal::factory()->for($this->alice)->create();

        expect($this->alice->can('update', $meal))->toBeTrue()
            ->and($this->bob->can('update', $meal))->toBeFalse();
    });

    it('allows a user to delete their own meal', function (): void {
        $meal = Meal::factory()->for($this->alice)->create();

        expect($this->alice->can('delete', $meal))->toBeTrue()
            ->and($this->bob->can('delete', $meal))->toBeFalse();
    });

    it('allows a user to view their own profile', function (): void {
        $profile = Profile::factory()->for($this->alice)->create();

        expect($this->alice->can('view', $profile))->toBeTrue()
            ->and($this->bob->can('view', $profile))->toBeFalse();
    });

    it('allows a user to update their own profile', function (): void {
        $profile = Profile::factory()->for($this->alice)->create();

        expect($this->alice->can('update', $profile))->toBeTrue()
            ->and($this->bob->can('update', $profile))->toBeFalse();
    });
});
