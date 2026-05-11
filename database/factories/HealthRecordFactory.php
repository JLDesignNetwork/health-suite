<?php

namespace Database\Factories;

use App\Models\HealthRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthRecord>
 */
class HealthRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'weight' => fake()->randomFloat(2, 50, 120),
            'neck' => fake()->randomFloat(2, 30, 50),
            'waist' => fake()->randomFloat(2, 60, 120),
            'hip' => fake()->optional()->randomFloat(2, 80, 130),
            'systolic' => fake()->numberBetween(100, 140),
            'diastolic' => fake()->numberBetween(60, 95),
            'pulse' => fake()->numberBetween(50, 100),
            'water_intake_l' => fake()->randomFloat(2, 0.5, 4.0),
            'exercise_minutes' => fake()->numberBetween(0, 90),
        ];
    }
}
