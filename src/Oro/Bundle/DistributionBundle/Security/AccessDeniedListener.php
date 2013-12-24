<?php

namespace Oro\Bundle\DistributionBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AccessDeniedListener
{
    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    protected $session;

    /**
     * @var \Symfony\Component\Routing\Router
     */
    protected $router;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param Session $session
     * @param Router $router
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(Session $session, Router $router, SecurityContextInterface $securityContext)
    {
        $this->session = $session;
        $this->router = $router;
        $this->securityContext = $securityContext;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onAccessDeniedException(GetResponseForExceptionEvent $event)
    {
        if ($event->getException() instanceof AccessDeniedHttpException) {
            $this->session->invalidate();
            $this->session->set(SecurityContextInterface::ACCESS_DENIED_ERROR, ['message' => 'You are not allowed']);
            $this->securityContext->setToken(null);

            $route = $this->router->generate('oro_distribution_security_login');

            $event->setResponse(new RedirectResponse($route));
        }
    }
}
