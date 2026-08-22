<?php

namespace App\Service\UserAction;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class UserActionRegistry
{
    /** @var array<string, UserActionInterface> */
    private array $actions = [];

    /**
     * @param iterable<UserActionInterface> $actions
     */
    public function __construct(
        #[AutowireIterator('app.user_action')] iterable $actions
    ) {
        foreach ($actions as $action) {
            $this->actions[$action->getName()] = $action;
        }
    }

    public function getAction(string $name): ?UserActionInterface
    {
        return $this->actions[$name] ?? null;
    }
}
