<?php

use App\Enums\MealType;
use App\Models\HealthRecord;
use App\Models\Meal;
use App\Models\Profile;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Profile::factory()->for($this->user)->create();
    $this->actingAs($this->user);
});

describe('history table', function (): void {
    it('renders the history page successfully', function (): void {
        $this->get(route('history'))->assertOk();
    });

    it('shows an empty state when no records exist', function (): void {
        $this->get(route('history'))
            ->assertSee('No health records yet');
    });

    it('shows health record data in the table', function (): void {
        HealthRecord::factory()->for($this->user)->create([
            'date' => '2026-01-15',
            'weight' => 75.5,
            'pulse' => 68,
        ]);

        $this->get(route('history'))
            ->assertSee('75.5')
            ->assertSee('68');
    });

    it('renders meal sub-rows for a date with multiple meals', function (): void {
        HealthRecord::factory()->for($this->user)->create(['date' => '2026-01-15']);

        Meal::factory()->for($this->user)->create([
            'date' => '2026-01-15',
            'meal_type' => MealType::Breakfast,
            'description' => 'Oatmeal',
            'calories' => 350,
        ]);
        Meal::factory()->for($this->user)->create([
            'date' => '2026-01-15',
            'meal_type' => MealType::Lunch,
            'description' => 'Chicken salad',
            'calories' => 520,
        ]);

        $response = $this->get(route('history'));
        $response->assertSee('Oatmeal')
            ->assertSee('Chicken salad')
            ->assertSee('350')
            ->assertSee('520');
    });

    it('applies the invisible class to date cells of meal sub-rows', function (): void {
        HealthRecord::factory()->for($this->user)->create(['date' => '2026-01-15']);
        Meal::factory()->for($this->user)->count(2)->create(['date' => '2026-01-15']);

        $this->get(route('history'))
            ->assertSee('class="px-4 py-2.5 whitespace-nowrap text-gray-500 invisible"', escape: false);
    });

    it('does not show another user\'s records', function (): void {
        $other = User::factory()->create();
        HealthRecord::factory()->for($other)->create([
            'date' => '2026-01-20',
            'weight' => 99.9,
        ]);

        $this->get(route('history'))
            ->assertDontSee('99.9');
    });

    it('streams a CSV export', function (): void {
        HealthRecord::factory()->for($this->user)->create([
            'date' => '2026-02-01',
            'weight' => 80.0,
        ]);
        Meal::factory()->for($this->user)->create([
            'date' => '2026-02-01',
            'meal_type' => MealType::Dinner,
            'description' => 'Pasta',
            'calories' => 700,
        ]);

        $response = $this->get(route('history.export'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('2026-02-01')
            ->assertSee('80')
            ->assertSee('Pasta')
            ->assertSee('700');
    });
});
