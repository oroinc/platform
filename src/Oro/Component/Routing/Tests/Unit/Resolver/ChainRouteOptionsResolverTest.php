<?php

namespace Oro\Component\Routing\Tests\Unit\Resolver;

use Oro\Component\Routing\Resolver\ChainRouteOptionsResolver;

class ChainRouteOptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyChainResolver()
    {
        $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
            ->disableOriginalConstructor()
            ->getMock();

        $routeCollectionAccessor = $this->getMockBuilder('Oro\Component\Routing\Resolver\RouteCollectionAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $chainResolver = new ChainRouteOptionsResolver();
        $chainResolver->resolve($route, $routeCollectionAccessor);
    }

    public function testChainResolver()
    {
        $route = $this->getMockBuilder('Symfony\Component\Routing\Route')
            ->disableOriginalConstructor()
            ->getMock();

        $routeCollectionAccessor = $this->getMockBuilder('Oro\Component\Routing\Resolver\RouteCollectionAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver1 = $this->getMock('Oro\Component\Routing\Resolver\RouteOptionsResolverInterface');
        $resolver2 = $this->getMock('Oro\Component\Routing\Resolver\RouteOptionsResolverInterface');

        $chainResolver = new ChainRouteOptionsResolver();
        $chainResolver->addResolver($resolver1);
        $chainResolver->addResolver($resolver2);

        $resolver1->expects($this->once())
            ->method('resolve')
            ->with($this->identicalTo($route), $this->identicalTo($routeCollectionAccessor));
        $resolver2->expects($this->once())
            ->method('resolve')
            ->with($this->identicalTo($route), $this->identicalTo($routeCollectionAccessor));

        $chainResolver->resolve($route, $routeCollectionAccessor);
    }
}
