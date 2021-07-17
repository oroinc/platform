<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Logs failed user login attempts.
 */
class LoginAttemptsLogSubscriber implements EventSubscriberInterface
{
    /** @var LoginAttemptsHandlerInterface */
    private $loginAttemptsHandler;

    public function __construct(LoginAttemptsHandlerInterface $loginAttemptsHandler)
    {
        $this->loginAttemptsHandler = $loginAttemptsHandler;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            SecurityEvents::INTERACTIVE_LOGIN            => 'onInteractiveLogin',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $this->loginAttemptsHandler->onAuthenticationFailure($event);
    }

    /**
     * {@inheritDoc}
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $this->loginAttemptsHandler->onInteractiveLogin($event);
    }
}
