<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * Represents a subscriber handler that is used to track user login attempts.
 */
interface LoginAttemptsHandlerInterface
{
    public function onInteractiveLogin(InteractiveLoginEvent $event): void;

    public function onAuthenticationFailure(LoginFailureEvent $event): void;
}
