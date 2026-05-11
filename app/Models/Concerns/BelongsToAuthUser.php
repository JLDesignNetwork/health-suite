<?php

namespace App\Models\Concerns;

use App\Models\Scopes\OwnedByAuthUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAuthUser
{
    public static function bootBelongsToAuthUser(): void
    {
        static::addGlobalScope(new OwnedByAuthUser);

        static::creating(function (self $model): void {
            if (! $model->user_id && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
