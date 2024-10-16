<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use FOS\RestBundle\EventListener\BodyListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Adapts {@see \FOS\RestBundle\EventListener\BodyListener} to BodyListenerInterface.
 */
class FosRestBodyListenerAdapter implements BodyListenerInterface
{
    private const HTTP_METHOD_OVERRIDE_HEADER_NAME = 'X-HTTP-METHOD-OVERRIDE';

    private BodyListener $listener;

    public function __construct(BodyListener $listener)
    {
        $this->listener = $listener;
    }

    #[\Override]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $methodOverride = $request->headers->get(self::HTTP_METHOD_OVERRIDE_HEADER_NAME);
        if ($methodOverride
            && Request::METHOD_GET === strtoupper($methodOverride)
            && Request::METHOD_POST === $request->getRealMethod()
        ) {
            $request->headers->remove(self::HTTP_METHOD_OVERRIDE_HEADER_NAME);
            // clear $this->method in the Request object to force recalculation of the request "intended" method
            // as it depends on the "X-HTTP-Method-Override" header
            $request->setMethod(Request::METHOD_POST);
            try {
                $this->listener->onKernelRequest($event);
            } finally {
                $request->headers->set(self::HTTP_METHOD_OVERRIDE_HEADER_NAME, $methodOverride);
                // clear $this->method in the Request object to force recalculation of the request method
                // as it depends on the "X-HTTP-Method-Override" header
                $request->setMethod(Request::METHOD_POST);
            }
        } else {
            $this->listener->onKernelRequest($event);
        }
    }
}
