<?php

namespace Oro\Component\Routing\Tests\Unit\Resolver;

use Oro\Component\Routing\Resolver\SortableRouteCollection;
use Symfony\Component\Routing\Route;

class SortableRouteCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testSortByPriority()
    {
        $routes = new SortableRouteCollection();

        $routes->add('route1', new Route('/path1'));
        $routes->add('route2', new Route('/path2'));
        $routes->add('route3', new Route('/path3', [], [], ['priority' => 1]));
        $routes->add('route4', new Route('/path4', [], [], ['priority' => 1]));
        $routes->add('route5', new Route('/path5', [], [], ['priority' => 0]));
        $routes->add('route6', new Route('/path6', [], [], ['priority' => 2]));
        $routes->add('route7', new Route('/path7', [], [], []));

        $routes->sortByPriority();

        $this->assertSame(
            ['route6', 'route3', 'route4', 'route1', 'route2', 'route5', 'route7'],
            array_keys($routes->all())
        );
    }
}
