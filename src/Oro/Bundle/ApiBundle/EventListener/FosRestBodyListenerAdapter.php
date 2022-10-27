<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use FOS\RestBundle\EventListener\BodyListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Adapts {@see \FOS\RestBundle\EventListener\BodyListener} to BodyListenerInterface.
 */
class FosRestBodyListenerAdapter implements BodyListenerInterface
{
    private BodyListener $listener;

    public function __construct(BodyListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $this->listener->onKernelRequest($event);
    }
}
