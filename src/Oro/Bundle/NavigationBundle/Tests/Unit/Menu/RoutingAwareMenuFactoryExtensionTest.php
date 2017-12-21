<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\MenuFactory;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\NavigationBundle\Menu\RoutingAwareMenuFactoryExtension;

class RoutingAwareMenuFactoryExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface */
    protected $router;

    /** @var MenuFactory */
    protected $factory;

    /** @var RoutingAwareMenuFactoryExtension */
    protected $factoryExtension;

    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);

        $this->factoryExtension = new RoutingAwareMenuFactoryExtension($this->router);

        $this->factory = new MenuFactory();
        $this->factory->addExtension($this->factoryExtension);
    }

    public function testBuildOptionsWithEmptyRoute()
    {
        $this->router->expects($this->never())
            ->method('generate');

        $item = $this->factory->createItem('test', []);

        $this->assertEmpty($item->getExtras());
        $this->assertNull($item->getUri());
    }

    public function testBuildOptionsWithDefaultKeys()
    {
        $route   = 'test';
        $uri     = '#';
        $options = ['route' => $route];
        $this->router->expects($this->once())
            ->method('generate')
            ->with($route, [], false)
            ->willReturn($uri);

        $item = $this->factory->createItem('test', $options);

        $this->assertEquals($uri, $item->getUri());
        $this->assertEquals([$route], $item->getExtra('routes'));
        $this->assertEquals([$route => []], $item->getExtra('routesParameters'));
    }

    public function testBuildOptionsWithKeys()
    {
        $route       = 'test';
        $uri         = '#';
        $routeParams = ['id' => 1];
        $routes      = ['test_*'];
        $options     = [
            'route' => $route,
            'extras' => ['acl_resource_id' => 'id', 'isAllowed' => true, 'routes' => $routes],
            'routeParameters' => $routeParams
        ];
        $this->router->expects($this->once())
            ->method('generate')
            ->with($route, $routeParams, false)
            ->willReturn($uri);

        $item = $this->factory->createItem('test', $options);

        $this->assertEquals($uri, $item->getUri());
        $this->assertEquals(array_merge_recursive([$route], $routes), $item->getExtra('routes'));
        $this->assertEquals([$route => $routeParams], $item->getExtra('routesParameters'));
    }
}
