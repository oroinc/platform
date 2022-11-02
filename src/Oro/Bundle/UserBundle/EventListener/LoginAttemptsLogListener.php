<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\UserBundle\Security\LoginAttemptsHandlerInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Delegates handling of success and failed login to a specified handler.
 */
class LoginAttemptsLogListener
{
    private LoginAttemptsHandlerInterface $loginAttemptsHandler;

    public function __construct(LoginAttemptsHandlerInterface $loginAttemptsHandler)
    {
        $this->loginAttemptsHandler = $loginAttemptsHandler;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $this->loginAttemptsHandler->onInteractiveLogin($event);
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $this->loginAttemptsHandler->onAuthenticationFailure($event);
    }
}
