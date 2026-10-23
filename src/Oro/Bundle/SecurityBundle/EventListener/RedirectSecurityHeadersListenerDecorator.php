<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Decorates a NelmioSecurityBundle listener so it adds its security headers to a redirect response too,
 * because NelmioSecurityBundle does not add them to redirects.
 */
final class RedirectSecurityHeadersListenerDecorator implements EventSubscriberInterface
{
    /**
     * @param object $innerListener A listener with onKernelResponse(ResponseEvent $event)
     */
    public function __construct(
        private readonly object $innerListener
    ) {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if (!$response->isRedirection()) {
            $this->innerListener->onKernelResponse($event);

            return;
        }

        $statusCode = $response->getStatusCode();
        $response->setStatusCode(Response::HTTP_OK);
        try {
            $this->innerListener->onKernelResponse($event);
        } finally {
            $response->setStatusCode($statusCode);
        }
    }
}
