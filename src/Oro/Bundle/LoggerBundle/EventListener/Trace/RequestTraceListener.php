<?php

namespace Oro\Bundle\LoggerBundle\EventListener\Trace;

use Oro\Bundle\LoggerBundle\Trace\TraceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Listener to set a unique trace ID for each request.
 * The ID is extracted from Request header.
 */
class RequestTraceListener
{
    public function __construct(
        private readonly TraceManagerInterface $traceManager,
        private readonly string $traceIdHeader,
    ) {
    }

    public function onRequest(RequestEvent $event): void
    {
        if (null !== $this->traceManager->get()) {
            return;
        }

        $request = $event->getRequest();
        $requestTraceId = $this->getRequestTrace($request);
        $this->traceManager->set($requestTraceId);
    }

    private function getRequestTrace(Request $request): ?string
    {
        return $request->headers->get($this->traceIdHeader);
    }
}
