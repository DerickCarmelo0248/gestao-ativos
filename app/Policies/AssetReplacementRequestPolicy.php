<?php

namespace App\Policies;

use App\Models\AssetReplacementRequest;
use App\Models\User;

class AssetReplacementRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function complete(
        User $user,
        AssetReplacementRequest $replacement
    ): bool {
        return in_array($user->role, ['admin', 'operator'], true)
            && in_array(
                $replacement->status,
                ['pending', 'purchasing'],
                true
            )
            && $replacement->replacement_asset_id === null
            && $replacement->replacement_movement_id === null;
    }
}