<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Concerns\BelongsToAuthUser;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'gender',
    'dob',
    'height_cm',
    'baseline_weight',
    'baseline_neck',
    'baseline_waist',
    'baseline_hip',
    'baseline_pulse',
    'baseline_systolic',
    'baseline_diastolic',
    'target_weight',
    'daily_calorie_goal',
    'daily_water_goal',
    'weekly_exercise_goal',
])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use BelongsToAuthUser, HasFactory;

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'dob' => 'date',
            'height_cm' => 'decimal:2',
            'baseline_weight' => 'decimal:2',
            'baseline_neck' => 'decimal:2',
            'baseline_waist' => 'decimal:2',
            'baseline_hip' => 'decimal:2',
            'target_weight' => 'decimal:2',
            'daily_water_goal' => 'decimal:2',
        ];
    }
}
