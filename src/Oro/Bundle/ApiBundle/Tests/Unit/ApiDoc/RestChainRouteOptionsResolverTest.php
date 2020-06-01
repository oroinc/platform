<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RestChainRouteOptionsResolver;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\ApiDoc\RestRouteOptionsResolver;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;

class RestChainRouteOptionsResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RestDocViewDetector */
    private $docViewDetector;

    protected function setUp(): void
    {
        $this->docViewDetector = $this->createMock(RestDocViewDetector::class);
    }

    /**
     * @param array $resolvers       [[resolver, view name], ...]
     * @param array $underlyingViews [view name => underlying view name, ...]
     *
     * @return RestChainRouteOptionsResolver
     */
    private function getChainRouteOptionsResolver(
        array $resolvers = [],
        array $underlyingViews = []
    ): RestChainRouteOptionsResolver {
        return new RestChainRouteOptionsResolver(
            $resolvers,
            $this->docViewDetector,
            $underlyingViews
        );
    }

    public function testEmptyChainResolver()
    {
        $route = $this->createMock(Route::class);
        $routes = $this->createMock(RouteCollectionAccessor::class);

        $this->docViewDetector->expects(self::never())
            ->method('getView');

        $chainRouteOptionsResolver = $this->getChainRouteOptionsResolver();
        $chainRouteOptionsResolver->resolve($route, $routes);
    }

    public function testEmptyView()
    {
        $route = $this->createMock(Route::class);
        $routes = $this->createMock(RouteCollectionAccessor::class);

        $resolver1 = $this->createMock(RouteOptionsResolverInterface::class);

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('');

        $resolver1->expects(self::never())
            ->method('resolve');

        $chainRouteOptionsResolver = $this->getChainRouteOptionsResolver(
            [[$resolver1, null]]
        );
        $chainRouteOptionsResolver->resolve($route, $routes);
    }

    public function testResolve()
    {
        $route = $this->createMock(Route::class);
        $routes = $this->createMock(RouteCollectionAccessor::class);

        $resolver1 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver2 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver3 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver4 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver5 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver6 = $this->createMock(RouteOptionsResolverInterface::class);

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('testView');

        $resolver1->expects(self::once())
            ->method('resolve')
            ->with(self::identicalTo($route), self::identicalTo($routes));
        $resolver2->expects(self::once())
            ->method('resolve')
            ->with(self::identicalTo($route), self::identicalTo($routes));
        $resolver3->expects(self::never())
            ->method('resolve');
        $resolver4->expects(self::once())
            ->method('resolve')
            ->with(self::identicalTo($route), self::identicalTo($routes));
        $resolver5->expects(self::once())
            ->method('resolve')
            ->with(self::identicalTo($route), self::identicalTo($routes));
        $resolver6->expects(self::never())
            ->method('resolve');

        $chainRouteOptionsResolver = $this->getChainRouteOptionsResolver(
            [
                [$resolver1, null],
                [$resolver2, 'testView'],
                [$resolver3, 'anotherView'],
                [$resolver4, null],
                [$resolver5, 'testView'],
                [$resolver6, 'anotherView']
            ]
        );
        $chainRouteOptionsResolver->resolve($route, $routes);
    }

    public function testResolveForViewWithUnderlyingView()
    {
        $route = $this->createMock(Route::class);
        $routes = $this->createMock(RouteCollectionAccessor::class);

        $resolver1 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver2 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver3 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver4 = $this->createMock(RouteOptionsResolverInterface::class);

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('testView');

        $resolver1->expects(self::once())
            ->method('resolve')
            ->with(self::identicalTo($route), self::identicalTo($routes));
        $resolver2->expects(self::once())
            ->method('resolve')
            ->with(self::identicalTo($route), self::identicalTo($routes));
        $resolver3->expects(self::once())
            ->method('resolve')
            ->with(self::identicalTo($route), self::identicalTo($routes));
        $resolver4->expects(self::never())
            ->method('resolve');

        $chainRouteOptionsResolver = $this->getChainRouteOptionsResolver(
            [
                [$resolver1, 'testView'],
                [$resolver2, 'underlyingView'],
                [$resolver3, null],
                [$resolver4, 'anotherView']
            ],
            ['testView' => 'underlyingView']
        );
        $chainRouteOptionsResolver->resolve($route, $routes);
    }

    public function testReset()
    {
        $resolver1 = $this->createMock(RouteOptionsResolverInterface::class);
        $resolver2 = $this->createMock(RestRouteOptionsResolver::class);

        $resolver2->expects(self::once())
            ->method('reset');

        $chainRouteOptionsResolver = $this->getChainRouteOptionsResolver(
            [[$resolver1, null], [$resolver2, null]]
        );
        $chainRouteOptionsResolver->reset();
    }
}
