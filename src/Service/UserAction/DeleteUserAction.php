<?php

namespace App\Service\UserAction;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DeleteUserAction implements UserActionInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function execute(User $user): void
    {
        $this->entityManager->remove($user);
    }

    public function getName(): string
    {
        return 'delete';
    }

    public function isSelfAffecting(): bool
    {
        return true;
    }
}
