<?php

namespace Oro\Bundle\DistributionBundle\Routing;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;

abstract class AbstractLoader extends YamlFileLoader
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param FileLocatorInterface $locator
     * @param KernelInterface $kernel
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(
        FileLocatorInterface $locator,
        KernelInterface $kernel,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct($locator);

        $this->kernel = $kernel;
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
