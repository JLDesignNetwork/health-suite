<?php

namespace App\Policies;

use App\Models\HealthRecord;
use App\Models\User;

final class HealthRecordPolicy
{
    public function view(User $user, HealthRecord $record): bool
    {
        return $user->id === $record->user_id;
    }

    public function update(User $user, HealthRecord $record): bool
    {
        return $user->id === $record->user_id;
    }

    public function delete(User $user, HealthRecord $record): bool
    {
        return $user->id === $record->user_id;
    }
}
