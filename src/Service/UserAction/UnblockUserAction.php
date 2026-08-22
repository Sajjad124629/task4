<?php

namespace App\Service\UserAction;

use App\Entity\User;

class UnblockUserAction implements UserActionInterface
{
    public function execute(User $user): void
    {
        if ($user->getStatus() === User::STATUS_BLOCKED) {
            $restoredStatus = $user->getPreviousStatus() ?? User::STATUS_ACTIVE;
            $user->setStatus($restoredStatus);
            $user->setPreviousStatus(null);
        }
    }

    public function getName(): string
    {
        return 'unblock';
    }

    public function isSelfAffecting(): bool
    {
        return false;
    }
}
