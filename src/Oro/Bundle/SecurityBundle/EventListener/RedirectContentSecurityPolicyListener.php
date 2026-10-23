<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\EventListener;

use Nelmio\SecurityBundle\EventListener\ContentSecurityPolicyListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Adds the NelmioSecurityBundle Content-Security-Policy headers to a redirect response,
 * because NelmioSecurityBundle does not add them to redirects.
 * The CSP listener is not decorated, because the NelmioSecurityBundle Twig runtime depends on it.
 */
final class RedirectContentSecurityPolicyListener
{
    public function __construct(
        private readonly ?ContentSecurityPolicyListener $cspListener
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if (null === $this->cspListener || !$response->isRedirection()) {
            return;
        }

        $statusCode = $response->getStatusCode();
        $response->setStatusCode(Response::HTTP_OK);
        try {
            $this->cspListener->onKernelResponse($event);
        } finally {
            $response->setStatusCode($statusCode);
        }
    }
}
