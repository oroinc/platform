<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\DistributionBundle\EventListener\RoutePrioritizingListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutePrioritizingListenerTest extends TestCase
{
    private RoutePrioritizingListener $listener;

    protected function setUp(): void
    {
        $this->listener = new RoutePrioritizingListener();
    }

    public function testOnCollectionLoadWithNoPrioritiesSet(): void
    {
        $collection = new RouteCollection();
        $event = new RouteCollectionEvent($collection);

        // No routes priorities set, so nothing should change in the collection.
        $this->listener->onCollectionLoad($event);

        self::assertSame($collection, $event->getCollection());
    }

    public function testOnCollectionLoadWithPrioritiesSet(): void
    {
        $collection = new RouteCollection();
        $route1 = new Route('/default');
        $route2 = new Route('/admin');
        $collection->add('oro_default', $route1);
        $collection->add('oro_admin', $route2);

        $event = new RouteCollectionEvent($collection);

        $this->listener->setRoutesPriorities([
            '/^oro_default$/' => 50,
            '/^oro_admin$/' => 100,
        ]);

        $this->listener->onCollectionLoad($event);

        $sortedRoutes = $event->getCollection();

        self::assertSame(2, $sortedRoutes->count());
        self::assertSame(['oro_admin', 'oro_default'], array_keys(iterator_to_array($sortedRoutes)));
    }

    public function testOnCollectionLoadWithNoMatchingPriorities(): void
    {
        $collection = new RouteCollection();
        $route1 = new Route('/default');
        $route2 = new Route('/admin');
        $collection->add('oro_default', $route1);
        $collection->add('oro_admin', $route2);

        $event = new RouteCollectionEvent($collection);

        // Set a priority rule that doesn't match any route names.
        $this->listener->setRoutesPriorities([
            '/^oro_nonexistent$/' => 50,
        ]);

        $this->listener->onCollectionLoad($event);

        $sortedRoutes = $event->getCollection();

        // Since no routes matched the rule, the order should remain the same.
        self::assertSame(2, $sortedRoutes->count());
        self::assertSame(['oro_default', 'oro_admin'], array_keys(iterator_to_array($sortedRoutes)));
    }
}
