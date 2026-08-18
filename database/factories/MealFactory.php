<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
class MealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'meal_type' => fake()->randomElement(MealType::cases()),
            'description' => fake()->sentence(3),
            'calories' => fake()->numberBetween(100, 1200),
        ];
    }
}
