<?php

namespace Oro\Bundle\InstallerBundle\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * The request listener that handles HTTP requests in case the application is not installed yet.
 * This listener should be registered only if the application is not installed yet.
 */
class RequestListener
{
    /** @var bool */
    private $debug;

    /**
     * @param bool $debug
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
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
            $event->setResponse(new RedirectResponse($event->getRequest()->getBasePath() . '/notinstalled.html'));
        }

        $event->stopPropagation();
    }
}
