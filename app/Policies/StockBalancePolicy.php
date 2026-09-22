<?php

namespace App\Policies;

use App\Models\User;

class StockBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function recordEntry(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }
}