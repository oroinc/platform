<?php

namespace Oro\Component\Routing\Tests\Unit\Resolver;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Oro\Component\Routing\Resolver\RouteCollectionAccessor;

class RouteCollectionAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var RouteCollection */
    protected $collection;

    /** @var RouteCollectionAccessor */
    protected $accessor;

    protected function setUp()
    {
        $this->collection = new RouteCollection();
        $this->accessor   = new RouteCollectionAccessor($this->collection);
    }

    public function testFindRouteByPath()
    {
        $route2Get  = new Route('/route2', [], [], [], '', [], 'GET');
        $route2Post = new Route('/route2', [], [], [], '', [], 'POST');

        $this->collection->add('route1', new Route('/route1'));
        $this->collection->add('route2_get', $route2Get);
        $this->collection->add('route2_post', $route2Post);

        $this->assertNull($this->accessor->findRouteByPath('/route1', ['GET']));
        $this->assertSame($route2Get, $this->accessor->findRouteByPath('/route2', ['GET']));
        $this->assertSame($route2Post, $this->accessor->findRouteByPath('/route2', ['POST']));
    }
}
