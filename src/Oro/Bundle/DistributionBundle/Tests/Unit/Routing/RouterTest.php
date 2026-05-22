<?php

declare(strict_types=1);

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Routing;

use Oro\Bundle\DistributionBundle\Event\RouterGenerateEvent;
use Oro\Bundle\DistributionBundle\Routing\Router;
use Oro\Bundle\DistributionBundle\Tests\Unit\Stub\RouterStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router as SymfonyRouter;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class RouterTest extends TestCase
{
    private RouterInterface&MockObject $innerRouter;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private Router $router;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerRouter = $this->createMock(RouterStub::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->router = new Router($this->innerRouter);
        $this->router->setEventDispatcher($this->eventDispatcher);
    }

    public function testSetContext(): void
    {
        $context = new RequestContext();

        $this->innerRouter
            ->expects(self::once())
            ->method('setContext')
            ->with($context);

        $this->router->setContext($context);
    }

    public function testGetContext(): void
    {
        $context = new RequestContext();

        $this->innerRouter
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($context);

        $result = $this->router->getContext();

        self::assertSame($context, $result);
    }

    public function testMatchRequest(): void
    {
        $request = new Request();
        $expectedMatch = ['_route' => 'test_route', 'id' => 123];

        $this->innerRouter
            ->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willReturn($expectedMatch);

        $result = $this->router->matchRequest($request);

        self::assertEquals($expectedMatch, $result);
    }

    public function testMatch(): void
    {
        $pathinfo = '/test/path';
        $expectedMatch = ['_route' => 'test_route', 'slug' => 'path'];

        $this->innerRouter
            ->expects(self::once())
            ->method('match')
            ->with($pathinfo)
            ->willReturn($expectedMatch);

        $result = $this->router->match($pathinfo);

        self::assertEquals($expectedMatch, $result);
    }

    public function testGetRouteCollection(): void
    {
        $collection = new RouteCollection();

        $this->innerRouter
            ->expects(self::once())
            ->method('getRouteCollection')
            ->willReturn($collection);

        $result = $this->router->getRouteCollection();

        self::assertSame($collection, $result);
    }

    public function testGenerateWithoutEventDispatcher(): void
    {
        $router = new Router($this->innerRouter);

        $this->innerRouter
            ->expects(self::once())
            ->method('generate')
            ->with('test_route', ['id' => 1], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/test/1');

        $result = $router->generate('test_route', ['id' => 1]);

        self::assertEquals('/test/1', $result);
    }

    public function testGenerateDispatchesEvent(): void
    {
        $routeName = 'test_route';
        $parameters = ['id' => 1];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL;

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(
                    static function (RouterGenerateEvent $event) use ($routeName, $parameters, $referenceType) {
                        return $event->getRouteName() === $routeName
                            && $event->getParameters() === $parameters
                            && $event->getReferenceType() === $referenceType;
                    }
                )
            );

        $this->innerRouter
            ->expects(self::once())
            ->method('generate')
            ->with($routeName, $parameters, $referenceType)
            ->willReturn('http://example.com/test/1');

        $this->router->generate($routeName, $parameters, $referenceType);
    }

    public function testGenerateUsesModifiedEventData(): void
    {
        $originalRouteName = 'original_route';
        $modifiedRouteName = 'modified_route';
        $originalParameters = ['id' => 1];
        $modifiedParameters = ['id' => 2, 'extra' => 'value'];
        $originalReferenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $modifiedReferenceType = UrlGeneratorInterface::ABSOLUTE_URL;

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (RouterGenerateEvent $event) use (
                $modifiedRouteName,
                $modifiedParameters,
                $modifiedReferenceType
            ) {
                $event->setRouteName($modifiedRouteName);
                $event->setParameters($modifiedParameters);
                $event->setReferenceType($modifiedReferenceType);

                return $event;
            });

        $this->innerRouter
            ->expects(self::once())
            ->method('generate')
            ->with($modifiedRouteName, $modifiedParameters, $modifiedReferenceType)
            ->willReturn('http://example.com/modified/2');

        $result = $this->router->generate($originalRouteName, $originalParameters, $originalReferenceType);

        self::assertEquals('http://example.com/modified/2', $result);
    }

    public function testGenerateWithDefaultReferenceType(): void
    {
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (RouterGenerateEvent $event) {
                    return $event->getReferenceType() === UrlGeneratorInterface::ABSOLUTE_PATH;
                })
            );

        $this->innerRouter
            ->expects(self::once())
            ->method('generate')
            ->with('test_route', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/test');

        $this->router->generate('test_route');
    }

    public function testGenerateWithEmptyParameters(): void
    {
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (RouterGenerateEvent $event) {
                    return $event->getParameters() === [];
                })
            );

        $this->innerRouter
            ->expects(self::once())
            ->method('generate')
            ->with('test_route', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/test');

        $this->router->generate('test_route');
    }

    public function testSetEventDispatcherToNull(): void
    {
        $this->router->setEventDispatcher(null);

        $this->innerRouter
            ->expects(self::once())
            ->method('generate')
            ->with('test_route', [], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/test');

        $result = $this->router->generate('test_route');

        self::assertEquals('/test', $result);
    }

    public function testWarmUpWhenInnerRouterIsWarmable(): void
    {
        $innerRouter = $this->createMock(RouterStub::class);
        $router = new Router($innerRouter);

        $cacheDir = '/path/to/cache';

        $innerRouter
            ->expects(self::once())
            ->method('warmUp')
            ->with($cacheDir);

        $router->warmUp($cacheDir);
    }

    public function testWarmUpWhenInnerRouterIsNotWarmable(): void
    {
        $innerRouter = $this->createMock(SymfonyRouter::class);
        $innerRouter
            ->method('getContext')
            ->willReturn(new RequestContext());
        $innerRouter
            ->method('matchRequest')
            ->willReturn([]);

        $router = new Router($innerRouter);

        $router->warmUp('/path/to/cache');

        self::assertTrue(true);
    }
}
