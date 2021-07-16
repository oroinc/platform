<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Represents the listener that handles the request body decoding.
 * This interface is required to be able to build a decoration chain.
 */
interface BodyListenerInterface
{
    public function onKernelRequest(RequestEvent $event): void;
}
