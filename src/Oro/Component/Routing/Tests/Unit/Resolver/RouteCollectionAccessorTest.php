<?php

namespace Oro\Component\Routing\Tests\Unit\Resolver;

use Oro\Component\Routing\Resolver\EnhancedRouteCollection;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteCollectionAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EnhancedRouteCollection */
    protected $collection;

    /** @var RouteCollectionAccessor */
    protected $accessor;

    protected function setUp()
    {
        $this->collection = new EnhancedRouteCollection();
        $this->accessor   = new RouteCollectionAccessor($this->collection);
    }

    public function testGetByPath()
    {
        $route2Get  = new Route('/route2', [], [], [], '', [], 'GET');
        $route2Post = new Route('/route2', [], [], [], '', [], 'POST');

        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('route2_get', $route2Get);
        $this->collection->add('route2_post', $route2Post);

        $this->assertNull($this->accessor->getByPath('/route1', ['GET']));
        $this->assertSame($route2Get, $this->accessor->getByPath('/route2', ['GET']));
        $this->assertSame($route2Post, $this->accessor->getByPath('/route2', ['POST']));
    }

    public function testGetName()
    {
        $route1 = new Route('/route1');
        $route2 = new Route('/route2');

        $this->collection->add('route1', $route1);
        $this->collection->add('route2', $route2);

        $this->assertEquals('route1', $this->accessor->getName($route1));
        $this->assertEquals('route2', $this->accessor->getName($route2));
        $this->assertNull($this->accessor->getName(new Route('/route1')));
    }

    public function testGet()
    {
        $route1 = new Route('/route1');
        $route2 = new Route('/route2');

        $this->collection->add('route1', $route1);
        $this->collection->add('route2', $route2);

        $this->assertSame($route1, $this->accessor->get('route1'));
        $this->assertSame($route2, $this->accessor->get('route2'));
        $this->assertNull($this->accessor->get('unknown'));
    }

    /**
     * @dataProvider insertDataProvider
     */
    public function testInsert($targetRouteName, $prepend, $expected)
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('route2', new Route('/route2'));

        $testRoute = new Route('/test');

        $this->accessor->insert('test', $testRoute, $targetRouteName, $prepend);
        $this->assertEquals($expected, array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    /**
     * @dataProvider insertDataProvider
     */
    public function testInsertExistingRoute($targetRouteName, $prepend, $expected)
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('test', new Route('/test'));
        $this->collection->add('route2', new Route('/route2'));

        $testRoute = new Route('/test');

        $this->accessor->insert('test', $testRoute, $targetRouteName, $prepend);
        $this->assertEquals($expected, array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    /**
     * @dataProvider insertDataProvider
     */
    public function testInsertWithAlreadyBuiltRouteMap($targetRouteName, $prepend, $expected)
    {
        $route1 = new Route('/route1');
        $route2 = new Route('/route2');

        $this->collection->add('route1', $route1);
        $this->collection->add('route2', $route2);

        // force the route map building
        $this->assertSame($route1, $this->accessor->getByPath('/route1', []));

        $testRoute = new Route('/test');

        $this->accessor->insert('test', $testRoute, $targetRouteName, $prepend);
        $this->assertEquals($expected, array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    /**
     * @dataProvider insertDataProvider
     */
    public function testInsertCollection($targetRouteName, $prepend, $expected)
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('route2', new Route('/route2'));
        $resource1 = new TestResource('resource1');
        $this->collection->addResource($resource1);

        $testRoute = new Route('/test');
        $collection = new RouteCollection();
        $collection->add('test', $testRoute);
        $testResource = new TestResource('test resource');
        $collection->addResource($testResource);

        $this->accessor->insertCollection($collection, $targetRouteName, $prepend);
        $this->assertEquals($expected, array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
        $resources = $this->collection->getResources();
        $this->assertCount(2, $resources);
        $this->assertSame($resource1, $resources[0]);
        $this->assertSame($testResource, $resources[1]);
    }

    /**
     * @dataProvider insertDataProvider
     */
    public function testInsertCollectionWithExistingRoute($targetRouteName, $prepend, $expected)
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('test', new Route('/test'));
        $this->collection->add('route2', new Route('/route2'));

        $testRoute = new Route('/test');
        $collection = new RouteCollection();
        $collection->add('test', $testRoute);

        $this->accessor->insertCollection($collection, $targetRouteName, $prepend);
        $this->assertEquals($expected, array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    /**
     * @dataProvider insertDataProvider
     */
    public function testInsertCollectionWithAlreadyBuiltRouteMap($targetRouteName, $prepend, $expected)
    {
        $route1 = new Route('/route1');
        $route2 = new Route('/route2');

        $this->collection->add('route1', $route1);
        $this->collection->add('route2', $route2);

        // force the route map building
        $this->assertSame($route1, $this->accessor->getByPath('/route1', []));

        $testRoute = new Route('/test');
        $collection = new RouteCollection();
        $collection->add('test', $testRoute);

        $this->accessor->insertCollection($collection, $targetRouteName, $prepend);
        $this->assertEquals($expected, array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function insertDataProvider()
    {
        return [
            [null, false, ['route1', 'route2', 'test']],
            [null, true, ['test', 'route1', 'route2']],
            ['route1', false, ['route1', 'test', 'route2']],
            ['route1', true, ['test', 'route1', 'route2']],
            ['route2', false, ['route1', 'route2', 'test']],
            ['route2', true, ['route1', 'test', 'route2']],
            ['unknown', false, ['route1', 'route2', 'test']],
            ['unknown', true, ['test', 'route1', 'route2']]
        ];
    }

    public function testAppend()
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('route2', new Route('/route2'));

        $testRoute = new Route('/test');

        $this->accessor->append('test', $testRoute);
        $this->assertEquals(['route1', 'route2', 'test'], array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function testAppendExistingRoute()
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('test', new Route('/test'));
        $this->collection->add('route2', new Route('/route2'));

        $testRoute = new Route('/test');

        $this->accessor->append('test', $testRoute);
        $this->assertEquals(['route1', 'route2', 'test'], array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function testAppendWithAlreadyBuiltRouteMap()
    {
        $route1 = new Route('/route1');
        $route2 = new Route('/route2');

        $this->collection->add('route1', $route1);
        $this->collection->add('route2', $route2);

        // force the route map building
        $this->assertSame($route1, $this->accessor->getByPath('/route1', []));

        $testRoute = new Route('/test');

        $this->accessor->append('test', $testRoute);
        $this->assertEquals(['route1', 'route2', 'test'], array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function testAppendToEmptyCollection()
    {
        $testRoute = new Route('/test');

        $this->accessor->append('test', $testRoute);
        $this->assertEquals(['test'], array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function testPrepend()
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('route2', new Route('/route2'));

        $testRoute = new Route('/test');

        $this->accessor->prepend('test', $testRoute);
        $this->assertEquals(['test', 'route1', 'route2'], array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function testPrependExistingRoute()
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('test', new Route('/test'));
        $this->collection->add('route2', new Route('/route2'));

        $testRoute = new Route('/test');

        $this->accessor->prepend('test', $testRoute);
        $this->assertEquals(['test', 'route1', 'route2'], array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function testPrependWithAlreadyBuiltRouteMap()
    {
        $route1 = new Route('/route1');
        $route2 = new Route('/route2');

        $this->collection->add('route1', $route1);
        $this->collection->add('route2', $route2);

        // force the route map building
        $this->assertSame($route1, $this->accessor->getByPath('/route1', []));

        $testRoute = new Route('/test');

        $this->accessor->prepend('test', $testRoute);
        $this->assertEquals(['test', 'route1', 'route2'], array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function testPrependToEmptyCollection()
    {
        $testRoute = new Route('/test');

        $this->accessor->prepend('test', $testRoute);
        $this->assertEquals(['test'], array_keys($this->collection->all()));
        $this->assertSame($testRoute, $this->accessor->getByPath('/test', []));
    }

    public function testRemove()
    {
        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('route2', new Route('/route2'));

        $this->accessor->remove('route1');
        $this->assertEquals(['route2'], array_keys($this->collection->all()));
        $this->assertNull($this->accessor->getByPath('/route1', []));
    }

    public function testRemoveWithAlreadyBuiltRouteMap()
    {
        $route1 = new Route('/route1');
        $route2 = new Route('/route2');

        $this->collection->add('route1', $route1);
        $this->collection->add('route2', $route2);

        // force the route map building
        $this->assertSame($route1, $this->accessor->getByPath('/route1', []));

        $this->accessor->remove('route1');
        $this->assertEquals(['route2'], array_keys($this->collection->all()));
        $this->assertNull($this->accessor->getByPath('/route1', []));
    }
}
