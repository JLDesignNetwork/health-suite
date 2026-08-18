<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'servings', 'estimated_calories_per_serving', 'instructions'])]
class Recipe extends Model
{
    use BelongsToAuthUser, HasFactory;
}
