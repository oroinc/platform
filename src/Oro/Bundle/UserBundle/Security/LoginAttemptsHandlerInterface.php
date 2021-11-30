<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Represents a subscriber handler that is used to track user login attempts.
 */
interface LoginAttemptsHandlerInterface
{
    public function onInteractiveLogin(InteractiveLoginEvent $event): void;

    public function onAuthenticationFailure(AuthenticationFailureEvent $event): void;
}
