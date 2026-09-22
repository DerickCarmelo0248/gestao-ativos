<?php

namespace App\Policies;

use App\Models\User;

class AssetPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }
}