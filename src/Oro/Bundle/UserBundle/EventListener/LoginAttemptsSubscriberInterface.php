<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Represents a subscriber that is used to track user login attempts.
 */
interface LoginAttemptsSubscriberInterface extends EventSubscriberInterface
{
    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event);

    /**
     * @param  InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event);
}
