<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RestPrefixRouteOptionsResolver;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Route;

class RestPrefixRouteOptionsResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RouteCollectionAccessor */
    private $routeCollectionAccessor;

    /** @var RestPrefixRouteOptionsResolver */
    private $routeOptionsResolver;

    protected function setUp()
    {
        $container = new Container();
        $container->setParameter('oro_api.rest.prefix', '/api/');
        $this->routeCollectionAccessor = $this->createMock(RouteCollectionAccessor::class);
        $this->routeOptionsResolver = new RestPrefixRouteOptionsResolver($container);
    }

    public function testResolvePath()
    {
        $route = new Route('%oro_api.rest.prefix%{entity}');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);
        self::assertEquals('/api/{entity}', $route->getPath());
    }

    public function testPathThatDoesNotRequireResolving()
    {
        $route = new Route('/another');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);
        self::assertEquals('/another', $route->getPath());
    }

    public function testResolveOverridePathOption()
    {
        $route = new Route('/api/test');
        $route->setOption('override_path', '%oro_api.rest.prefix%test/{id}');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);
        self::assertEquals('/api/test/{id}', $route->getOption('override_path'));
    }

    public function testOverridePathOptionThatDoesNotRequireResolving()
    {
        $route = new Route('/api/test');
        $route->setOption('override_path', '/another/test');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);
        self::assertEquals('/another/test', $route->getOption('override_path'));
    }
}
