<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'dietary_regimen',
    'food_restrictions',
    'caffeine_intake',
    'physical_activity',
    'sleep_hours',
    'sleep_notes',
    'tobacco_use',
    'alcohol_use',
    'substance_notes',
    'wellness_goals',
])]
class LifestyleProfile extends Model
{
    use BelongsToAuthUser, HasFactory;

    protected function casts(): array
    {
        return [
            'sleep_hours' => 'decimal:1',
        ];
    }
}
