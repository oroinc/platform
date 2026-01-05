<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\EntityUrlProvider;
use Oro\Bundle\EntityConfigBundle\Provider\EntityUrlProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    private RouterInterface|MockObject $router;
    private ConfigManager|MockObject $configManager;
    private EntityUrlProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new EntityUrlProvider($this->router, $this->configManager);
    }

    public function testGetRouteReturnsNullWhenEntityHasNoConfig(): void
    {
        $entityClass = \stdClass::class;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $this->configManager->expects($this->never())
            ->method('getEntityMetadata');

        $result = $this->provider->getRoute($entityClass);

        $this->assertNull($result);
    }

    public function testGetRouteReturnsNullWhenEntityMetadataIsNull(): void
    {
        $entityClass = \stdClass::class;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn(null);

        $result = $this->provider->getRoute($entityClass);

        $this->assertNull($result);
    }

    public function testGetRouteReturnsRouteWhenRouteExists(): void
    {
        $entityClass = \stdClass::class;
        $expectedRoute = 'test_entity_index';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routeName = $expectedRoute;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute)
            ->willReturn('/test/url');

        $result = $this->provider->getRoute($entityClass, EntityUrlProviderInterface::ROUTE_INDEX);

        $this->assertEquals($expectedRoute, $result);
    }

    public function testGetRouteReturnsNullWhenRouterDoesNotHaveRoute(): void
    {
        $entityClass = \stdClass::class;
        $routeName = 'non_existent_route';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routeName = $routeName;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($routeName)
            ->willThrowException(new RouteNotFoundException());

        $result = $this->provider->getRoute($entityClass, EntityUrlProviderInterface::ROUTE_INDEX);

        $this->assertNull($result);
    }

    public function testGetRouteWithObjectEntity(): void
    {
        $entity = new \stdClass();
        $entityClass = \stdClass::class;
        $expectedRoute = 'test_entity_view';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routeView = $expectedRoute;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute)
            ->willReturn('/test/view');

        $result = $this->provider->getRoute($entity, EntityUrlProviderInterface::ROUTE_VIEW);

        $this->assertEquals($expectedRoute, $result);
    }

    public function testGetRouteForViewRouteType(): void
    {
        $entityClass = \stdClass::class;
        $expectedRoute = 'test_entity_view';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routeView = $expectedRoute;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute)
            ->willReturn('/test/view');

        $result = $this->provider->getRoute($entityClass, EntityUrlProviderInterface::ROUTE_VIEW);

        $this->assertEquals($expectedRoute, $result);
    }

    public function testGetRouteForUpdateRouteType(): void
    {
        $entityClass = \stdClass::class;
        $expectedRoute = 'test_entity_update';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routes = ['update' => $expectedRoute];

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute)
            ->willReturn('/test/update');

        $result = $this->provider->getRoute($entityClass, EntityUrlProviderInterface::ROUTE_UPDATE);

        $this->assertEquals($expectedRoute, $result);
    }

    public function testGetRouteForCreateRouteType(): void
    {
        $entityClass = \stdClass::class;
        $expectedRoute = 'test_entity_create';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routeCreate = $expectedRoute;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($expectedRoute)
            ->willReturn('/test/create');

        $result = $this->provider->getRoute($entityClass, EntityUrlProviderInterface::ROUTE_CREATE);

        $this->assertEquals($expectedRoute, $result);
    }

    public function testGetIndexUrl(): void
    {
        $entityClass = \stdClass::class;
        $routeName = 'test_entity_index';
        $expectedUrl = '/test/index';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routeName = $routeName;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnCallback(function ($route, $params = []) use ($routeName, $expectedUrl) {
                if ($route === $routeName && empty($params)) {
                    return $expectedUrl;
                }
                return '/test/url';
            });

        $result = $this->provider->getIndexUrl($entityClass);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetViewUrl(): void
    {
        $entityClass = \stdClass::class;
        $entityId = 123;
        $routeName = 'test_entity_view';
        $expectedUrl = '/test/view/123';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routeView = $routeName;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnCallback(function ($route, $params = []) use ($routeName, $expectedUrl) {
                if ($route === $routeName && isset($params['id']) && $params['id'] === 123) {
                    return $expectedUrl;
                }
                return '/test/url';
            });

        $result = $this->provider->getViewUrl($entityClass, $entityId);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetUpdateUrl(): void
    {
        $entityClass = \stdClass::class;
        $entityId = 456;
        $routeName = 'test_entity_update';
        $expectedUrl = '/test/update/456';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routes = ['update' => $routeName];

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnCallback(function ($route, $params = []) use ($routeName, $expectedUrl) {
                if ($route === $routeName && isset($params['id']) && $params['id'] === 456) {
                    return $expectedUrl;
                }
                return '/test/url';
            });

        $result = $this->provider->getUpdateUrl($entityClass, $entityId);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetCreateUrl(): void
    {
        $entityClass = \stdClass::class;
        $routeName = 'test_entity_create';
        $expectedUrl = '/test/create';

        $metadata = new EntityMetadata($entityClass);
        $metadata->routeCreate = $routeName;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnCallback(function ($route, $params = []) use ($routeName, $expectedUrl) {
                if ($route === $routeName && empty($params)) {
                    return $expectedUrl;
                }
                return '/test/url';
            });

        $result = $this->provider->getCreateUrl($entityClass);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testGetIndexUrlReturnsNullWhenNoConfig(): void
    {
        $entityClass = \stdClass::class;

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $result = $this->provider->getIndexUrl($entityClass);

        $this->assertNull($result);
    }
}
