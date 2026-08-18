<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'allergen',
    'category',
    'severity',
    'reaction',
    'treatment',
])]
class Allergy extends Model
{
    use BelongsToAuthUser, HasFactory;
}
