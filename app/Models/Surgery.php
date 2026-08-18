<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'procedure',
    'date_year',
    'facility',
    'surgeon',
    'notes',
])]
class Surgery extends Model
{
    use BelongsToAuthUser, HasFactory;
}
