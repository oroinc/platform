<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Represents a subscriber handler that is used to track user login attempts.
 */
interface LoginAttemptsHandlerInterface
{
    const SUCCESSFUL_LOGIN_MESSAGE = 'Successful login';
    const UNSUCCESSFUL_LOGIN_MESSAGE = 'Unsuccessful login';

    public function onAuthenticationFailure(AuthenticationFailureEvent $event);

    public function onInteractiveLogin(InteractiveLoginEvent $event);
}
