<?php

namespace App\Policies;

use App\Models\User;

class CategoryPolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }
}