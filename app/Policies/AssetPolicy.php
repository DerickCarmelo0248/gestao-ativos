<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Asset;

class AssetPolicy
{
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function viewAny(User $user): bool
    {
    return in_array($user->role, ['admin', 'operator'], true);
    }

    public function view(User $user, Asset $asset): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }  

    public function recordExit(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function recordReturn(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }
}