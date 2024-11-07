<?php

namespace Oro\Bundle\DistributionBundle\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Symfony\Component\Routing\Route;

/**
 * Changes priorities of the routes specified via ::setRoutesPriorities method.
 */
class RoutePrioritizingListener
{
    /**
     * @var array<string,int>
     *  [
     *      // route name regexp => route priority
     *      '/^oro_default$/' => 50,
     *  ]
     */
    private array $routesPriorities = [];

    /**
     * @param array<string,int> $routesPriorities
     *  [
     *      // route name regexp => route priority
     *      '/^oro_default$/' => 50,
     *  ]
     */
    public function setRoutesPriorities(array $routesPriorities): void
    {
        $this->routesPriorities = $routesPriorities;
    }

    public function onCollectionLoad(RouteCollectionEvent $event): void
    {
        if (!$this->routesPriorities) {
            return;
        }

        /** @var Route $route */
        $collection = $event->getCollection();
        foreach ($collection as $routeName => $route) {
            foreach ($this->routesPriorities as $routeRegexp => $priority) {
                if (preg_match($routeRegexp, $routeName)) {
                    $collection->add($routeName, $route, $priority);
                    break;
                }
            }
        }
    }
}
