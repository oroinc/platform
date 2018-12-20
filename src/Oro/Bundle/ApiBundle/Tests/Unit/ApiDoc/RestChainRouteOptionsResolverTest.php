<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\RestChainRouteOptionsResolver;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Component\Routing\Resolver\RouteCollectionAccessor;
use Oro\Component\Routing\Resolver\RouteOptionsResolverInterface;
use Symfony\Component\Routing\Route;

class RestChainRouteOptionsResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|RestDocViewDetector */
    private $docViewDetector;

    /** @var RestChainRouteOptionsResolver */
    private $chainRouteOptionsResolver;

    protected function setUp()
    {
        $this->docViewDetector = $this->createMock(RestDocViewDetector::class);

        $this->chainRouteOptionsResolver = new RestChainRouteOptionsResolver($this->docViewDetector);
    }

    public function testEmptyChainResolver()
    {
        $route = $this->createMock(Route::class);
        $routes = $this->createMock(RouteCollectionAccessor::class);

        $this->docViewDetector->expects(self::never())
            ->method('getView');

        $this->chainRouteOptionsResolver->resolve($route, $routes);
    }

    public function testEmptyView()
    {
        $route = $this->createMock(Route::class);
        $routes = $this->createMock(RouteCollectionAccessor::class);

        $resolver1 = $this->createMock(RouteOptionsResolverInterface::class);
        $this->chainRouteOptionsResolver->addResolver($resolver1);

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('');

        $resolver1->expects(self::never())
            ->method('resolve');

        $this->chainRouteOptionsResolver->resolve($route, $routes);
    }

    public function testResolve()
    {
        $route = $this->createMock(Route::class);
        $routes = $this->createMock(RouteCollectionAccessor::class);

        $resolver1 = $this->createMock(RouteOptionsResolverInterface::class);
        $this->chainRouteOptionsResolver->addResolver($resolver1);
        $resolver2 = $this->createMock(RouteOptionsResolverInterface::class);
        $this->chainRouteOptionsResolver->addResolver($resolver2, 'testView');
        $resolver3 = $this->createMock(RouteOptionsResolverInterface::class);
        $this->chainRouteOptionsResolver->addResolver($resolver3, 'anotherView');
        $resolver4 = $this->createMock(RouteOptionsResolverInterface::class);
        $this->chainRouteOptionsResolver->addResolver($resolver4);
        $resolver5 = $this->createMock(RouteOptionsResolverInterface::class);
        $this->chainRouteOptionsResolver->addResolver($resolver5, 'testView');
        $resolver6 = $this->createMock(RouteOptionsResolverInterface::class);
        $this->chainRouteOptionsResolver->addResolver($resolver6, 'anotherView');

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

        $this->chainRouteOptionsResolver->resolve($route, $routes);
    }
}
