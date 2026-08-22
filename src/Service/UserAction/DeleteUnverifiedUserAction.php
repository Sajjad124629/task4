<?php

namespace App\Service\UserAction;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DeleteUnverifiedUserAction implements UserActionInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function execute(User $user): void
    {
        if ($user->getStatus() === User::STATUS_UNVERIFIED) {
            $this->entityManager->remove($user);
        }
    }

    public function getName(): string
    {
        return 'delete_unverified';
    }

    public function isSelfAffecting(): bool
    {
        return false;
    }
}
