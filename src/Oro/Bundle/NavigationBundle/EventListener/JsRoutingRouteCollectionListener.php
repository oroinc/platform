<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

/**
 * Moves definition of a route to a controller used to get exposed to JS routes to the end of the routes collection.
 * It is required to be sure that the "oro_gaufrette_public_file" route is used to get exposed routes
 * when the debug mode is disabled.
 */
class JsRoutingRouteCollectionListener
{
    private string $jsRoutingRouteName;
    private bool $debug;

    public function __construct(string $jsRoutingRouteName, bool $debug)
    {
        $this->jsRoutingRouteName = $jsRoutingRouteName;
        $this->debug = $debug;
    }

    public function onCollectionAutoload(RouteCollectionEvent $event): void
    {
        if ($this->debug) {
            return;
        }

        $collection = $event->getCollection();
        $jsRoutingRoute = $collection->get($this->jsRoutingRouteName);
        if (null === $jsRoutingRoute) {
            throw new \LogicException(sprintf('The "%s" route does not exist.', $this->jsRoutingRouteName));
        }
        $collection->remove($this->jsRoutingRouteName);
        $collection->add($this->jsRoutingRouteName, $jsRoutingRoute, -20);
    }
}
