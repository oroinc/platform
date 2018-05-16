<?php

namespace Oro\Bundle\InstallerBundle\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /**
     * Installed flag
     *
     * @var bool
     */
    protected $installed;

    /**
     * Debug flag
     *
     * @var bool
     */
    protected $debug;

    /**
     * @param bool $installed
     * @param bool $debug
     */
    public function __construct($installed, $debug = false)
    {
        $this->installed = $installed;
        $this->debug     = $debug;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$this->installed) {
            $allowedRoutes = [];
            if ($this->debug) {
                $allowedRoutes = [
                    '_wdt',
                    '_profiler',
                    '_profiler_search',
                    '_profiler_search_bar',
                    '_profiler_search_results',
                    '_profiler_router',
                ];
            }

            if (!in_array($event->getRequest()->get('_route'), $allowedRoutes, true)) {
                $event->setResponse(new RedirectResponse($event->getRequest()->getBasePath() . '/notinstalled.html'));
            }

            $event->stopPropagation();
        }
    }
}
