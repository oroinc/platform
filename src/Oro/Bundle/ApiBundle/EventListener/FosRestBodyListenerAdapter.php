<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use FOS\RestBundle\EventListener\BodyListener;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Adapts {@see \FOS\RestBundle\EventListener\BodyListener} to BodyListenerInterface.
 */
class FosRestBodyListenerAdapter implements BodyListenerInterface
{
    /** @var BodyListener */
    private $listener;

    public function __construct(BodyListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritDoc}
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $this->listener->onKernelRequest($event);
    }
}
