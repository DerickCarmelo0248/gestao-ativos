<?php

namespace App\Policies;

use App\Models\User;

class ItemPolicy
{
    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, \App\Models\Item $item): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, \App\Models\Item $item): bool
    {
        return $user->role === 'admin';
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }
}
