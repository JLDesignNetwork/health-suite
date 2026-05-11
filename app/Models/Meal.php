<?php

namespace App\Models;

use App\Enums\MealType;
use App\Models\Concerns\BelongsToAuthUser;
use Database\Factories\MealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'date',
    'meal_type',
    'description',
    'calories',
])]
class Meal extends Model
{
    /** @use HasFactory<MealFactory> */
    use BelongsToAuthUser, HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'meal_type' => MealType::class,
        ];
    }
}
