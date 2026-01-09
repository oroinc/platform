<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Provider\AbstractEntityUrlProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AbstractEntityUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    private RouterInterface|MockObject $router;
    private AbstractEntityUrlProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->provider = new class ($this->router) extends AbstractEntityUrlProvider {
            public function __construct(RouterInterface $router)
            {
                $this->router = $router;
            }

            public function getRoute(
                object|string $entity,
                string $routeType = self::ROUTE_INDEX,
                bool $throwExceptionIfNotDefined = false
            ): ?string {
                // Simple test implementation that returns a route based on the entity class name
                $className = \is_object($entity) ? \get_class($entity) : $entity;
                $baseRoute = \str_replace('\\', '_', \strtolower($className));

                return match ($routeType) {
                    self::ROUTE_INDEX => $baseRoute . '_index',
                    self::ROUTE_VIEW => $baseRoute . '_view',
                    self::ROUTE_UPDATE => $baseRoute . '_update',
                    self::ROUTE_CREATE => $baseRoute . '_create',
                    default => null,
                };
            }

            // Expose protected method for testing
            public function exposeRouterHasRoute(string $routeName): bool
            {
                return $this->routerHasRoute($routeName);
            }
        };
    }

    public function testGetIndexUrl(): void
    {
        $entity = new \stdClass();
        $expectedRoute = 'stdclass_index';
        $expectedUrl = '/test/index';
        $extraParams = ['param1' => 'value1'];

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, $extraParams)
            ->willReturn($expectedUrl);

        $result = $this->provider->getIndexUrl($entity, $extraParams);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetIndexUrlWithStringEntity(): void
    {
        $entity = \stdClass::class;
        $expectedRoute = 'stdclass_index';
        $expectedUrl = '/test/index';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, [])
            ->willReturn($expectedUrl);

        $result = $this->provider->getIndexUrl($entity);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetViewUrl(): void
    {
        $entity = new \stdClass();
        $entityId = 123;
        $expectedRoute = 'stdclass_view';
        $expectedUrl = '/test/view/123';
        $extraParams = ['param1' => 'value1'];
        $expectedParams = array_merge($extraParams, ['id' => $entityId]);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, $expectedParams)
            ->willReturn($expectedUrl);

        $result = $this->provider->getViewUrl($entity, $entityId, $extraParams);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetViewUrlWithoutExtraParams(): void
    {
        $entity = new \stdClass();
        $entityId = 456;
        $expectedRoute = 'stdclass_view';
        $expectedUrl = '/test/view/456';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, ['id' => $entityId])
            ->willReturn($expectedUrl);

        $result = $this->provider->getViewUrl($entity, $entityId);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetUpdateUrl(): void
    {
        $entity = new \stdClass();
        $entityId = 789;
        $expectedRoute = 'stdclass_update';
        $expectedUrl = '/test/update/789';
        $extraParams = ['param1' => 'value1'];
        $expectedParams = array_merge($extraParams, ['id' => $entityId]);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, $expectedParams)
            ->willReturn($expectedUrl);

        $result = $this->provider->getUpdateUrl($entity, $entityId, $extraParams);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetUpdateUrlWithoutExtraParams(): void
    {
        $entity = new \stdClass();
        $entityId = 321;
        $expectedRoute = 'stdclass_update';
        $expectedUrl = '/test/update/321';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, ['id' => $entityId])
            ->willReturn($expectedUrl);

        $result = $this->provider->getUpdateUrl($entity, $entityId);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetCreateUrl(): void
    {
        $entity = new \stdClass();
        $expectedRoute = 'stdclass_create';
        $expectedUrl = '/test/create';
        $extraParams = ['param1' => 'value1'];

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, $extraParams)
            ->willReturn($expectedUrl);

        $result = $this->provider->getCreateUrl($entity, $extraParams);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetCreateUrlWithoutExtraParams(): void
    {
        $entity = new \stdClass();
        $expectedRoute = 'stdclass_create';
        $expectedUrl = '/test/create';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, [])
            ->willReturn($expectedUrl);

        $result = $this->provider->getCreateUrl($entity);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetUrlReturnsNullWhenRouteIsNull(): void
    {
        // Create a provider that returns null for getRoute
        $provider = new class ($this->router) extends AbstractEntityUrlProvider {
            public function __construct(RouterInterface $router)
            {
                $this->router = $router;
            }

            public function getRoute(
                object|string $entity,
                string $routeType = self::ROUTE_INDEX,
                bool $throwExceptionIfNotDefined = false
            ): ?string {
                return null;
            }
        };

        $this->router->expects($this->never())
            ->method('generate');

        $result = $provider->getIndexUrl(new \stdClass());

        $this->assertNull($result);
    }

    public function testRouterHasRouteReturnsTrueWhenRouteExists(): void
    {
        $routeName = 'test_route';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($routeName)
            ->willReturn('/test/url');

        $result = $this->provider->exposeRouterHasRoute($routeName);

        $this->assertTrue($result);
    }

    public function testRouterHasRouteReturnsFalseWhenRouteNotFound(): void
    {
        $routeName = 'non_existent_route';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($routeName)
            ->willThrowException(new RouteNotFoundException());

        $result = $this->provider->exposeRouterHasRoute($routeName);

        $this->assertFalse($result);
    }

    public function testRouterHasRouteReturnsTrueWhenOtherRoutingExceptionThrown(): void
    {
        $routeName = 'test_route';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($routeName)
            ->willThrowException(new MissingMandatoryParametersException());

        $result = $this->provider->exposeRouterHasRoute($routeName);

        $this->assertTrue($result);
    }

    public function testGetIndexUrlUsesCorrectRouteType(): void
    {
        $entity = new \stdClass();
        $expectedRoute = 'stdclass_index';
        $expectedUrl = '/test/index';

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, [])
            ->willReturn($expectedUrl);

        $result = $this->provider->getIndexUrl($entity);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetViewUrlMergesIdWithExtraParams(): void
    {
        $entity = new \stdClass();
        $entityId = 100;
        $extraParams = ['sort' => 'name', 'order' => 'asc'];
        $expectedRoute = 'stdclass_view';
        $expectedUrl = '/test/view/100?sort=name&order=asc';
        $expectedParams = ['sort' => 'name', 'order' => 'asc', 'id' => 100];

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, $expectedParams)
            ->willReturn($expectedUrl);

        $result = $this->provider->getViewUrl($entity, $entityId, $extraParams);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetUpdateUrlMergesIdWithExtraParams(): void
    {
        $entity = new \stdClass();
        $entityId = 200;
        $extraParams = ['redirect' => 'list'];
        $expectedRoute = 'stdclass_update';
        $expectedUrl = '/test/update/200?redirect=list';
        $expectedParams = ['redirect' => 'list', 'id' => 200];

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, $expectedParams)
            ->willReturn($expectedUrl);

        $result = $this->provider->getUpdateUrl($entity, $entityId, $extraParams);

        $this->assertEquals($expectedUrl, $result);
    }
}
