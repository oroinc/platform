<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Routing;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Decorates the routing loader to dispatch a {@seee RouteCollectionEvent} after routes are loaded.
 */
class DelegatingLoader extends Loader
{
    public function __construct(
        private LoaderInterface $decoratedLoader,
        private EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, ?string $type = null): mixed
    {
        $routes = $this->decoratedLoader->load($resource, $type);

        $event = new RouteCollectionEvent($routes);
        $this->eventDispatcher->dispatch($event, RouteCollectionEvent::ALL);

        return $event->getCollection();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $this->decoratedLoader->supports($resource, $type);
    }
}
