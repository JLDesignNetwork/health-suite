<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

final class ProfilePolicy
{
    public function view(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    public function delete(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}
