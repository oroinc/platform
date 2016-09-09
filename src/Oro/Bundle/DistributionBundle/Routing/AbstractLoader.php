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

    /** @var SharedData */
    protected $cache;

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
     * Sets an object that can be used to share data between different loaders
     *
     * @param SharedData $cache
     */
    public function setCache(SharedData $cache = null)
    {
        $this->cache = $cache;
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

    /**
     * {@inheritdoc}
     */
    protected function loadRoutes(RouteCollection $routes)
    {
        if (null === $this->cache) {
            parent::loadRoutes($routes);
        } else {
            $resources = $this->getResources();
            foreach ($resources as $resource) {
                if (is_string($resource)) {
                    $resourceRoutes = $this->cache->getRoutes($resource);
                    if (null === $resourceRoutes) {
                        $resourceRoutes = $this->resolve($resource)->load($resource);
                        $this->cache->setRoutes($resource, $resourceRoutes);
                    }
                } else {
                    $resourceRoutes = $this->resolve($resource)->load($resource);
                }
                $routes->addCollection($resourceRoutes);
            }
        }
    }
}
