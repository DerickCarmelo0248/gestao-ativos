<?php

namespace App\Policies;

use App\Models\User;

class CategoryPolicy
{
    public function update(User $user, \App\Models\Category $category): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, \App\Models\Category $category): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }
}
