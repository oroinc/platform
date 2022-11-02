<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Provider;

use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\Controller\SomeController;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\Controller\TestController;
use Oro\Bundle\UIBundle\Tests\Unit\Fixture\Controller\TestInvokeController;
use Oro\Component\Config\Cache\PhpConfigCacheAccessor;
use Oro\Component\Testing\TempDirExtension;
use ProxyManager\Proxy\VirtualProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\ReflectionClassResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ControllerClassProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var RouteCollection */
    private $routeCollection;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ControllerClassProvider */
    private $controllerClassProvider;

    /** @var string */
    private $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = $this->getTempFile('ControllerClassProvider');

        $this->routeCollection = new RouteCollection();
        $this->container = $this->createMock(ContainerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::any())
            ->method('getRouteCollection')
            ->willReturn($this->routeCollection);

        $this->controllerClassProvider = new ControllerClassProvider(
            $this->cacheFile,
            true,
            $router,
            $this->container,
            $this->logger
        );
    }

    public function testGetControllers()
    {
        $this->routeCollection->add(
            'route1',
            new Route('route1', ['_controller' => TestController::class . '::someAction'])
        );
        $this->routeCollection->add(
            'route2',
            new Route('route2', ['_controller' => TestController::class . '::anotherAction'])
        );
        $this->routeCollection->add(
            'route3',
            new Route('route3', ['_controller' => SomeController::class . '::someAction'])
        );
        $this->routeCollection->add(
            'route4',
            new Route('route4', ['_controller' => 'some_controller.service_definition::anotherAction'])
        );

        $expectedControllers = [
            'route1' => [TestController::class, 'someAction'],
            'route2' => [TestController::class, 'anotherAction'],
            'route3' => [SomeController::class, 'someAction'],
            'route4' => [SomeController::class, 'anotherAction'],
        ];

        $this->container->expects(self::once())
            ->method('has')
            ->with('some_controller.service_definition')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('some_controller.service_definition')
            ->willReturn(new SomeController());
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            $expectedControllers,
            $this->controllerClassProvider->getControllers()
        );

        $dataAccessor = new PhpConfigCacheAccessor();
        self::assertSame(
            $expectedControllers,
            $dataAccessor->load(new ConfigCache($this->cacheFile, false))
        );

        $meta = unserialize(file_get_contents($this->cacheFile . '.meta'));
        self::assertCount(2, $meta);
        self::assertInstanceOf(ReflectionClassResource::class, $meta[0]);
        self::assertInstanceOf(ReflectionClassResource::class, $meta[1]);
    }

    public function testLoadForRouteWithoutController()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test')
        );

        $this->container->expects(self::never())
            ->method('has');
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            [],
            $this->controllerClassProvider->getControllers()
        );
    }

    public function testLoadForRouteWithNotSupportedController()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 123])
        );

        $this->container->expects(self::never())
            ->method('has');
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            [],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test for controller defined as "class::method"
     */
    public function testLoadClassMethod()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => TestController::class . '::someAction'])
        );

        $this->container->expects(self::never())
            ->method('has');
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            ['test_route' => [TestController::class, 'someAction']],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test for controller defined as "service:method"
     */
    public function testLoadControllerAsService()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 'test_controller:someAction'])
        );

        $this->container->expects(self::once())
            ->method('has')
            ->with('test_controller')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('test_controller')
            ->willReturn(new TestController());
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            ['test_route' => [TestController::class, 'someAction']],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test for controller defined as "service:method" and the controller service is lazy (initialized)
     */
    public function testLoadControllerAsInitializedLazyService()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 'test_controller:someAction'])
        );

        $service = $this->createMock(VirtualProxyInterface::class);
        $service->expects(self::once())
            ->method('isProxyInitialized')
            ->willReturn(true);
        $service->expects(self::never())
            ->method('initializeProxy');
        $service->expects(self::once())
            ->method('getWrappedValueHolderValue')
            ->willReturn(new TestController());

        $this->container->expects(self::once())
            ->method('has')
            ->with('test_controller')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('test_controller')
            ->willReturn($service);
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            ['test_route' => [TestController::class, 'someAction']],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test for controller defined as "service:method" and the controller service is lazy (not initialized)
     */
    public function testLoadControllerAsNotInitializedLazyService()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 'test_controller:someAction'])
        );

        $service = $this->createMock(VirtualProxyInterface::class);
        $service->expects(self::once())
            ->method('isProxyInitialized')
            ->willReturn(false);
        $service->expects(self::once())
            ->method('initializeProxy');
        $service->expects(self::once())
            ->method('getWrappedValueHolderValue')
            ->willReturn(new TestController());

        $this->container->expects(self::once())
            ->method('has')
            ->with('test_controller')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('test_controller')
            ->willReturn($service);
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            ['test_route' => [TestController::class, 'someAction']],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test for controller defined as "service:method" when service does not exist
     */
    public function testLoadControllerAsServiceWhenServiceDoesNotExist()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 'test_controller:someAction'])
        );

        $this->container->expects(self::once())
            ->method('has')
            ->with('test_controller')
            ->willReturn(false);
        $this->logger->expects(self::once())
            ->method('error')
            ->willReturnCallback(function ($message, $context) {
                self::assertEquals('Cannot extract controller for "test_route" route.', $message);
                /** @var \InvalidArgumentException $exception */
                $exception = $context['exception'];
                self::assertInstanceOf(\InvalidArgumentException::class, $exception);
                self::assertEquals('Undefined controller service "test_controller".', $exception->getMessage());
            });

        self::assertSame(
            [],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test for controller defined as "service"
     */
    public function testLoadControllerAsServiceWithInvokeMethod()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 'test_controller'])
        );

        $this->container->expects(self::once())
            ->method('has')
            ->with('test_controller')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('test_controller')
            ->willReturn(new TestInvokeController());
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            ['test_route' => [TestInvokeController::class, '__invoke']],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test for controller defined as "service" when service does not exist
     */
    public function testLoadControllerAsServiceWithInvokeMethodWhenServiceDoesNotExist()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 'test_controller'])
        );

        $this->container->expects(self::once())
            ->method('has')
            ->with('test_controller')
            ->willReturn(false);
        $this->logger->expects(self::once())
            ->method('error')
            ->willReturnCallback(function ($message, $context) {
                self::assertEquals('Cannot extract controller for "test_route" route.', $message);
                /** @var \InvalidArgumentException $exception */
                $exception = $context['exception'];
                self::assertInstanceOf(\InvalidArgumentException::class, $exception);
                self::assertEquals('Undefined controller service "test_controller".', $exception->getMessage());
            });

        self::assertSame(
            [],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test for controller defined as "service" when "__invoke" method does not exist
     */
    public function testLoadControllerAsServiceWithInvokeMethodWhenInvokeMethodDoesNotExist()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 'test_controller'])
        );

        $this->container->expects(self::once())
            ->method('has')
            ->with('test_controller')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('test_controller')
            ->willReturn(new TestController());
        $this->logger->expects(self::once())
            ->method('error')
            ->willReturnCallback(function ($message, $context) {
                self::assertEquals('Cannot extract controller for "test_route" route.', $message);
                /** @var \InvalidArgumentException $exception */
                $exception = $context['exception'];
                self::assertInstanceOf(\InvalidArgumentException::class, $exception);
                self::assertEquals(
                    sprintf('Controller class "%s" should have "__invoke" method.', TestController::class),
                    $exception->getMessage()
                );
            });

        self::assertSame(
            [],
            $this->controllerClassProvider->getControllers()
        );
    }

    /**
     * test case when the controller service should be ignored
     */
    public function testLoadControllerAsIgnoredService()
    {
        $this->routeCollection->add(
            'test_route',
            new Route('test', ['_controller' => 'web_profiler.controller'])
        );

        $this->container->expects(self::once())
            ->method('has')
            ->with('web_profiler.controller')
            ->willReturn(true);
        $this->container->expects(self::never())
            ->method('get');
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(
            [],
            $this->controllerClassProvider->getControllers()
        );
    }
}
