<?php

namespace Oro\Bundle\TestFrameworkBundle\Security;

use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Listener to stop login success event for test environment to prevent the removal of the csrf token
 * in storage before the test is performed.
 */
class CsrfSuccessLoginListener
{
    public function onSuccess(LoginSuccessEvent $event): void
    {
        // prevent csrf from being removed from the storage after successfully logging into tests.
        $event->stopPropagation();
    }
}
