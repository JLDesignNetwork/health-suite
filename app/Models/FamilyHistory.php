<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'relative',
    'conditions',
    'onset',
    'status',
])]
class FamilyHistory extends Model
{
    use BelongsToAuthUser, HasFactory;

    protected $table = 'family_history';
}
