<?php

namespace App\Service\UserAction;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.user_action')]
interface UserActionInterface
{
    /**
     * Executes the action on the given user.
     */
    public function execute(User $user): void;

    /**
     * Returns the unique name of the action (e.g., 'block', 'delete').
     */
    public function getName(): string;

    /**
     * Whether performing this action on oneself affects the current session.
     */
    public function isSelfAffecting(): bool;
}
