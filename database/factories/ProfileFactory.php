<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());

        return [
            'user_id' => User::factory(),
            'gender' => $gender,
            'dob' => fake()->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
            'height_cm' => fake()->randomFloat(2, 150, 200),
            'baseline_weight' => fake()->randomFloat(2, 50, 120),
            'baseline_neck' => fake()->randomFloat(2, 30, 50),
            'baseline_waist' => fake()->randomFloat(2, 60, 120),
            'baseline_hip' => $gender === Gender::Female ? fake()->randomFloat(2, 80, 130) : null,
            'baseline_pulse' => fake()->numberBetween(55, 85),
            'baseline_systolic' => fake()->numberBetween(105, 135),
            'baseline_diastolic' => fake()->numberBetween(65, 90),
            'target_weight' => fake()->randomFloat(2, 50, 100),
            'daily_calorie_goal' => fake()->numberBetween(1500, 3000),
            'daily_water_goal' => fake()->randomFloat(2, 1.5, 3.5),
            'weekly_exercise_goal' => fake()->numberBetween(90, 300),
        ];
    }
}
