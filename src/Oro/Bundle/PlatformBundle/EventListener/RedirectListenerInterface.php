<?php

namespace Oro\Bundle\PlatformBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * For listeners that will be responsible for possible redirecting
 */
interface RedirectListenerInterface
{
    public function onRequest(RequestEvent $event): void;
}
