<?php

namespace Oro\Bundle\DistributionBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Redirects user to login page if AccessDeniedHttpException is thrown.
 */
class AccessDeniedListener
{
    protected Session $session;

    protected Router $router;

    protected TokenStorageInterface $tokenStorage;

    public function __construct(Session $session, Router $router, TokenStorageInterface $tokenStorage)
    {
        $this->session = $session;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public function onAccessDeniedException(ExceptionEvent $event): void
    {
        if ($event->getThrowable() instanceof AccessDeniedHttpException) {
            $this->session->invalidate();
            $this->session->set(Security::ACCESS_DENIED_ERROR, ['message' => 'You are not allowed']);
            $this->tokenStorage->setToken(null);

            $route = $this->router->generate('oro_distribution_security_login');

            $event->setResponse(new RedirectResponse($route));
        }
    }
}
