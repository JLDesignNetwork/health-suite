<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\MealType;
use App\Models\HealthRecord;
use App\Models\Meal;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $demo = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@ihealth.test',
            'password' => Hash::make('password'),
        ]);

        Profile::create([
            'user_id' => $demo->id,
            'gender' => Gender::Male,
            'dob' => '1985-06-15',
            'height_cm' => 178.0,
            'baseline_weight' => 85.0,
            'baseline_neck' => 39.0,
            'baseline_waist' => 90.0,
            'baseline_hip' => null,
            'baseline_pulse' => 68,
            'baseline_systolic' => 120,
            'baseline_diastolic' => 80,
            'target_weight' => 78.0,
            'daily_calorie_goal' => 2200,
            'daily_water_goal' => 2.5,
            'weekly_exercise_goal' => 180,
        ]);

        // 30 days of health records
        $startWeight = 85.0;
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $weight = round($startWeight - ($i * 0.08) + fake()->randomFloat(1, -0.2, 0.2), 1);

            HealthRecord::create([
                'user_id' => $demo->id,
                'date' => $date,
                'weight' => $weight,
                'neck' => fake()->randomFloat(1, 38.0, 40.0),
                'waist' => fake()->randomFloat(1, 87.0, 93.0),
                'systolic' => fake()->numberBetween(115, 130),
                'diastolic' => fake()->numberBetween(76, 86),
                'pulse' => fake()->numberBetween(62, 74),
                'water_intake_l' => fake()->randomFloat(1, 1.5, 3.0),
                'exercise_minutes' => fake()->numberBetween(0, 60),
            ]);

            foreach ([MealType::Breakfast, MealType::Lunch, MealType::Dinner] as $type) {
                Meal::create([
                    'user_id' => $demo->id,
                    'date' => $date,
                    'meal_type' => $type,
                    'description' => match ($type) {
                        MealType::Breakfast => fake()->randomElement(['Oatmeal with banana', 'Eggs on toast', 'Greek yoghurt & granola']),
                        MealType::Lunch => fake()->randomElement(['Chicken salad', 'Tuna wrap', 'Lentil soup']),
                        MealType::Dinner => fake()->randomElement(['Grilled salmon & veg', 'Pasta primavera', 'Stir-fry chicken & rice']),
                    },
                    'calories' => match ($type) {
                        MealType::Breakfast => fake()->numberBetween(300, 500),
                        MealType::Lunch => fake()->numberBetween(450, 650),
                        MealType::Dinner => fake()->numberBetween(550, 800),
                    },
                ]);
            }
        }
    }
}
