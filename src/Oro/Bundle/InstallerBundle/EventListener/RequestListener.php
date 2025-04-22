<?php

namespace Oro\Bundle\InstallerBundle\EventListener;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * The request listener that handles HTTP requests in case the application is not installed yet.
 * This listener should be registered only if the application is not installed yet.
 */
class RequestListener
{
    public function __construct(
        private ApplicationState $applicationState,
        private string $templatePath,
        private bool $debug = false
    ) {
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($this->applicationState->isInstalled()) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $allowedRoutes = [];
        if ($this->debug) {
            $allowedRoutes = [
                '_wdt',
                '_profiler',
                '_profiler_search',
                '_profiler_search_bar',
                '_profiler_search_results',
                '_profiler_router'
            ];
        }

        if (!in_array($event->getRequest()->get('_route'), $allowedRoutes, true)) {
            $response = new Response(
                file_get_contents($this->templatePath),
                Response::HTTP_SERVICE_UNAVAILABLE
            );
            $event->setResponse($response);
        }

        $event->stopPropagation();
    }
}
