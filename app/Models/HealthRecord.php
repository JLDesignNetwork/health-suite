<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Database\Factories\HealthRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'date',
    'weight',
    'neck',
    'waist',
    'hip',
    'systolic',
    'diastolic',
    'pulse',
    'water_intake_l',
    'exercise_minutes',
])]
class HealthRecord extends Model
{
    /** @use HasFactory<HealthRecordFactory> */
    use BelongsToAuthUser, HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'weight' => 'decimal:2',
            'neck' => 'decimal:2',
            'waist' => 'decimal:2',
            'hip' => 'decimal:2',
            'water_intake_l' => 'decimal:2',
        ];
    }
}
