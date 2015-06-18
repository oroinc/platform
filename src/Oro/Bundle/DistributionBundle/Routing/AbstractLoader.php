<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouteCollection;

use Oro\Component\Routing\Loader\CumulativeRoutingFileLoader;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

abstract class AbstractLoader extends CumulativeRoutingFileLoader
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * Sets the event dispatcher
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $eventName
     * @param RouteCollection $routes
     * @return RouteCollection
     */
    protected function dispatchEvent($eventName, RouteCollection $routes)
    {
        if (!$this->eventDispatcher) {
            return $routes;
        }

        $event = new RouteCollectionEvent($routes);
        $this->eventDispatcher->dispatch($eventName, $event);

        return $event->getCollection();
    }
}
