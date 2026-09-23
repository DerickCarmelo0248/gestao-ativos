<?php

namespace App\Policies;

use App\Models\StockReplacementRequest;
use App\Models\User;

class StockReplacementRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function complete(
        User $user,
        StockReplacementRequest $replacement
    ): bool {
        return in_array($user->role, ['admin', 'operator'], true)
            && in_array(
                $replacement->status,
                ['pending', 'purchasing'],
                true
            );
    }
}