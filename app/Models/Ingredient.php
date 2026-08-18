<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'quantity', 'quantity_on_hand', 'unit', 'category', 'notes', 'shared'])]
class Ingredient extends Model
{
    use BelongsToAuthUser, HasFactory;

    protected function casts(): array
    {
        return ['quantity_on_hand' => 'decimal:2', 'shared' => 'boolean'];
    }
}
