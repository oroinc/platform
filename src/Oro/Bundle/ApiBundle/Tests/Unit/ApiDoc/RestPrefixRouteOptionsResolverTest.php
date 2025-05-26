<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RestPrefixRouteOptionsResolver;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\Route;

class RestPrefixRouteOptionsResolverTest extends TestCase
{
    private RouteCollectionAccessor&MockObject $routeCollectionAccessor;
    private RestPrefixRouteOptionsResolver $routeOptionsResolver;

    #[\Override]
    protected function setUp(): void
    {
        $container = new Container();
        $container->setParameter('oro_api.rest.prefix', '/api/');
        $this->routeCollectionAccessor = $this->createMock(RouteCollectionAccessor::class);
        $this->routeOptionsResolver = new RestPrefixRouteOptionsResolver($container);
    }

    public function testResolvePath(): void
    {
        $route = new Route('%oro_api.rest.prefix%{entity}');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);
        self::assertEquals('/api/{entity}', $route->getPath());
    }

    public function testPathThatDoesNotRequireResolving(): void
    {
        $route = new Route('/another');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);
        self::assertEquals('/another', $route->getPath());
    }

    public function testResolveOverridePathOption(): void
    {
        $route = new Route('/api/test');
        $route->setOption('override_path', '%oro_api.rest.prefix%test/{id}');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);
        self::assertEquals('/api/test/{id}', $route->getOption('override_path'));
    }

    public function testOverridePathOptionThatDoesNotRequireResolving(): void
    {
        $route = new Route('/api/test');
        $route->setOption('override_path', '/another/test');

        $this->routeOptionsResolver->resolve($route, $this->routeCollectionAccessor);
        self::assertEquals('/another/test', $route->getOption('override_path'));
    }
}
