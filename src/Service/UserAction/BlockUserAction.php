<?php

namespace App\Service\UserAction;

use App\Entity\User;

class BlockUserAction implements UserActionInterface
{
    public function execute(User $user): void
    {
        if ($user->getStatus() !== User::STATUS_BLOCKED) {
            $user->setPreviousStatus($user->getStatus());
            $user->setStatus(User::STATUS_BLOCKED);
        }
    }

    public function getName(): string
    {
        return 'block';
    }

    public function isSelfAffecting(): bool
    {
        return true;
    }
}
