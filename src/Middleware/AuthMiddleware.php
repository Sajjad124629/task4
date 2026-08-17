<?php

namespace App\Middleware;

use App\Attribute\Auth;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthMiddleware implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (is_array($controller)) {
            $class = get_class($controller[0]);
            $method = $controller[1];
        } elseif (is_object($controller)) {
            $class = get_class($controller);
            $method = '__invoke';
        } else {
            return;
        }

        try {
            $reflectionClass = new \ReflectionClass($class);
            $reflectionMethod = $reflectionClass->hasMethod($method) ? $reflectionClass->getMethod($method) : null;
        } catch (\ReflectionException $e) {
            return;
        }

        $hasAuthAttribute = !empty($reflectionClass->getAttributes(Auth::class)) ||
                            ($reflectionMethod && !empty($reflectionMethod->getAttributes(Auth::class)));

        if ($hasAuthAttribute) {
            $user = $this->security->getUser();
            
            if (!$user) {
                $event->setController(function () {
                    return new RedirectResponse($this->urlGenerator->generate('app_login'));
                });
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
          
            KernelEvents::CONTROLLER => ['onKernelController', 0],
        ];
    }
}
