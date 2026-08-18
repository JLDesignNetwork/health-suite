<?php

namespace App\Models;

use App\Enums\MealType;
use App\Models\Concerns\BelongsToAuthUser;
use Database\Factories\MealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'calories' => 'integer',
        ];
    }

    public function mealIngredients(): HasMany
    {
        return $this->hasMany(MealIngredient::class);
    }
}
