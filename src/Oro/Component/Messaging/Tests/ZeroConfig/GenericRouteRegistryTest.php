<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\ZeroConfig\GenericRouteRegistry;
use Oro\Component\Messaging\ZeroConfig\Route;

class GenericRouteRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAttributes()
    {
        new GenericRouteRegistry();
    }

    public function testCouldAddRoute()
    {
        $registry = new GenericRouteRegistry();

        // guard
        $this->assertEmpty($registry->getRoutes('topic'));

        $route = new Route();
        $route->setTopicName('topic');

        // test
        $registry->addRoute($route);

        $this->assertCount(1, $registry->getRoutes('topic'));
        $this->assertSame($route, $registry->getRoutes('topic')[0]);
    }

    public function testCouldSetManyRoutes()
    {
        $registry = new GenericRouteRegistry();

        // guard
        $this->assertEmpty($registry->getRoutes('topic'));

        $route1 = new Route();
        $route1->setTopicName('topic');
        $route2 = new Route();
        $route2->setTopicName('topic');

        // test
        $registry->setRoutes([$route1, $route2]);

        $this->assertCount(2, $registry->getRoutes('topic'));
        $this->assertSame($route1, $registry->getRoutes('topic')[0]);
        $this->assertSame($route2, $registry->getRoutes('topic')[1]);
    }
}
