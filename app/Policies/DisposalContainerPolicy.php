<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DisposalContainer;

class DisposalContainerPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'operator'], true);
    }
    
    public function addItem(
    User $user,
    DisposalContainer $container
    ): bool {
        return in_array($user->role, ['admin', 'operator'], true)
            && $container->status === 'open';
    }

    public function view(
        User $user,
        DisposalContainer $container
    ): bool {
        return in_array($user->role, ['admin', 'operator'], true);
    }

    public function close(
        User $user,
        DisposalContainer $container
    ): bool {
        return in_array($user->role, ['admin', 'operator'], true)
            && $container->status === 'open';
    }
}