<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(event: 'kernel.request')]
class CheckUserStatusListener
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (in_array($route, ['app_login', 'app_register', 'app_verify_email'])) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if ($user) {
            $freshUser = $this->entityManager->getRepository(User::class)->find($user->getId());

            if (!$freshUser || $freshUser->getStatus() === User::STATUS_BLOCKED) {
                $this->security->logout(false);
                
                $session = $request->getSession();
                if ($session instanceof FlashBagAwareSessionInterface) {
                    $session->getFlashBag()->add('error', 'Your account is blocked or deleted.');
                }
                
                $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
            }
        }
    }
}
