<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Oro\Bundle\DistributionBundle\Event\RouteCollectionEvent;
use Oro\Bundle\NavigationBundle\EventListener\JsRoutingRouteCollectionListener;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class JsRoutingRouteCollectionListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnCollectionAutoload(): void
    {
        $collection = new RouteCollection();
        $route1 = new Route('test1', ['_controller' => 'Test1:get']);
        $jsRoutingRoute = new Route('test1', ['_controller' => 'Test1:get']);
        $route2 = new Route('test2', ['_controller' => 'Test2:get']);
        $collection->add('test1', $route1);
        $collection->add('js', $jsRoutingRoute);
        $collection->add('test2', $route2);

        $listener = new JsRoutingRouteCollectionListener('js', false);
        $listener->onCollectionAutoload(new RouteCollectionEvent($collection));

        self::assertCount(3, $collection);
        self::assertEquals(
            [
                'test1' => $route1,
                'test2' => $route2,
                'js'    => $jsRoutingRoute
            ],
            $collection->all()
        );
        self::assertEquals(['test1', 'test2', 'js'], array_keys($collection->all()));
    }

    public function testOnCollectionAutoloadWhenJsRoutingRouteDoesNotExist(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "js" route does not exist.');

        $collection = new RouteCollection();
        $collection->add('test1', new Route('test1', ['_controller' => 'Test1:get']));

        $listener = new JsRoutingRouteCollectionListener('js', false);
        $listener->onCollectionAutoload(new RouteCollectionEvent($collection));
    }

    public function testOnCollectionAutoloadForDebug(): void
    {
        $collection = new RouteCollection();
        $route1 = new Route('test1', ['_controller' => 'Test1:get']);
        $jsRoutingRoute = new Route('test1', ['_controller' => 'Test1:get']);
        $route2 = new Route('test2', ['_controller' => 'Test2:get']);
        $collection->add('test1', $route1);
        $collection->add('js', $jsRoutingRoute);
        $collection->add('test2', $route2);

        $listener = new JsRoutingRouteCollectionListener('js', true);
        $listener->onCollectionAutoload(new RouteCollectionEvent($collection));

        self::assertCount(3, $collection);
        self::assertEquals(
            [
                'test1' => $route1,
                'js'    => $jsRoutingRoute,
                'test2' => $route2
            ],
            $collection->all()
        );
        self::assertEquals(['test1', 'js', 'test2'], array_keys($collection->all()));
    }
}
