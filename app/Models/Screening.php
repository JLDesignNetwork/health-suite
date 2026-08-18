<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'screening_type',
    'last_date',
    'next_due_date',
    'provider',
    'notes',
])]
class Screening extends Model
{
    use BelongsToAuthUser, HasFactory;

    protected function casts(): array
    {
        return [
            'last_date' => 'date',
            'next_due_date' => 'date',
        ];
    }
}
