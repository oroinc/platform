<?php

namespace Oro\Component\Routing\Tests\Unit\Resolver;

use Oro\Component\Routing\Resolver\ChainRouteOptionsResolver;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;

class ChainRouteOptionsResolverTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyChainResolver()
    {
        $route = $this->createMock(Route::class);

        $routeCollectionAccessor = $this->createMock(RouteCollectionAccessor::class);

        $chainResolver = new ChainRouteOptionsResolver([]);
        $chainResolver->resolve($route, $routeCollectionAccessor);
    }

    public function testChainResolver()
    {
        $route = $this->createMock(Route::class);

        $routeCollectionAccessor = $this->createMock(RouteCollectionAccessor::class);

        $resolver1 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver2 = $this->createMock(RouteOptionsResolverInterface::class);

        $chainResolver = new ChainRouteOptionsResolver([$resolver1, $resolver2]);

        $resolver1->expects($this->once())
            ->method('resolve')
            ->with($this->identicalTo($route), $this->identicalTo($routeCollectionAccessor));
        $resolver2->expects($this->once())
            ->method('resolve')
            ->with($this->identicalTo($route), $this->identicalTo($routeCollectionAccessor));

        $chainResolver->resolve($route, $routeCollectionAccessor);
    }
}
