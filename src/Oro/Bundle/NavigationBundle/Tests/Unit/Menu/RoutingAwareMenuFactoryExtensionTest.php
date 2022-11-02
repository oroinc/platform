<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu;

use Knp\Menu\MenuFactory;
use Oro\Bundle\NavigationBundle\Menu\RoutingAwareMenuFactoryExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class RoutingAwareMenuFactoryExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var MenuFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);

        $this->factory = new MenuFactory();
        $this->factory->addExtension(new RoutingAwareMenuFactoryExtension($this->router));
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
        $route = 'test';
        $uri = '#';
        $options = ['route' => $route];

        $context = new RequestContext();
        $this->router->expects($this->any())
            ->method('getContext')
            ->willReturn($context);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($route, [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($uri);

        $item = $this->factory->createItem('test', $options);

        $this->assertEquals($uri, $item->getUri());
        $this->assertEquals([$route], $item->getExtra('routes'));
        $this->assertEquals([$route => []], $item->getExtra('routesParameters'));
    }

    public function testBuildOptionsWithKeys()
    {
        $route = 'test';
        $uri = '#';
        $routeParams = ['id' => 1];
        $routes = ['test_*'];
        $options = [
            'route' => $route,
            'extras' => ['acl_resource_id' => 'id', 'isAllowed' => true, 'routes' => $routes],
            'routeParameters' => $routeParams
        ];

        $context = new RequestContext();
        $this->router->expects($this->any())
            ->method('getContext')
            ->willReturn($context);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($route, $routeParams, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($uri);

        $item = $this->factory->createItem('test', $options);

        $this->assertEquals($uri, $item->getUri());
        $this->assertEquals(array_merge_recursive([$route], $routes), $item->getExtra('routes'));
        $this->assertEquals([$route => $routeParams], $item->getExtra('routesParameters'));
    }
}
