<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'diagnosis_year',
    'status',
    'specialist',
    'notes',
])]
class Condition extends Model
{
    use BelongsToAuthUser, HasFactory;
}
