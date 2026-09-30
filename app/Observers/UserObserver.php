<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Support\AuditService;

class UserObserver
{
    public function created(User $user): void
    {
        AuditService::record('user.created', $user, null, $user->getAttributes());
    }

    public function updated(User $user): void
    {
        AuditService::record(
            'user.updated',
            $user,
            $user->getOriginal(),
            $user->getAttributes(),
        );
    }
}
