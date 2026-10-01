<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockBalance;

class StockBalancePolicy
{
    public function adjust(User $user, StockBalance $stockBalance): bool
    {
        return $user->role === 'admin' && $stockBalance->item->tracking_type === 'quantity'
            && $stockBalance->item->is_active && $stockBalance->unit->is_active;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function recordEntry(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function view(User $user, StockBalance $stockBalance): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }
    public function recordExit(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }
}
