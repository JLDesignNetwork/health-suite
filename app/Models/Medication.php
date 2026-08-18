<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'category',
    'form',
    'dosage',
    'frequency',
    'timing',
    'reason',
    'prescribing_doctor',
    'start_date',
    'status',
    'pill_color',
    'pill_shape',
    'notes',
])]
class Medication extends Model
{
    use BelongsToAuthUser, HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }
}
