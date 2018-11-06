<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResourcesProcessor;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|CollectResourcesProcessor */
    private $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ResourcesCache */
    private $resourcesCache;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    protected function setUp()
    {
        $this->processor = $this->createMock(CollectResourcesProcessor::class);
        $this->resourcesCache = $this->createMock(ResourcesCache::class);

        $this->resourcesProvider = new ResourcesProvider(
            $this->processor,
            $this->resourcesCache
        );
    }

    public function testGetResourcesNoCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $accessibleResources = ['Test\Entity2'];
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2')
        ];
        $expectedAccessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true
        ];
        $expectedExcludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));

                    $context->setAccessibleResources(['Test\Entity2']);
                }
            );
        $this->resourcesCache->expects(self::at(1))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $accessibleResources
            );
        $this->resourcesCache->expects(self::at(2))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(3))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedExcludedActions);

        self::assertEquals(
            $expectedResources,
            $this->resourcesProvider->getResources($version, $requestType)
        );
        // test memory cache
        self::assertEquals(
            $expectedResources,
            $this->resourcesProvider->getResources($version, $requestType)
        );
    }

    public function testGetResourcesFromCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2')
        ];
        $accessibleResources = [];
        $excludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::at(2))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');

        self::assertEquals(
            $resources,
            $this->resourcesProvider->getResources($version, $requestType)
        );
        // test memory cache
        self::assertEquals(
            $resources,
            $this->resourcesProvider->getResources($version, $requestType)
        );
    }

    public function testGetAccessibleResourcesWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true
        ];
        $excludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::at(2))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');

        self::assertEquals(
            ['Test\Entity2'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
        // test memory cache
        self::assertEquals(
            ['Test\Entity2'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
    }

    public function testGetAccessibleResourcesWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = ['Test\Entity2'];

        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2')
        ];
        $expectedAccessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true
        ];
        $expectedExcludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));

                    $context->setAccessibleResources(['Test\Entity2']);
                }
            );
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $accessibleResources
            );
        $this->resourcesCache->expects(self::at(3))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(4))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedExcludedActions);

        self::assertEquals(
            ['Test\Entity2'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
        // test memory cache
        self::assertEquals(
            ['Test\Entity2'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
    }

    public function testIsResourceAccessibleWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true
        ];
        $excludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::at(2))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');

        self::assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity2', $version, $requestType)
        );
        // test memory cache
        self::assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity2', $version, $requestType)
        );
    }

    public function testIsResourceAccessibleWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1')];
        $accessibleResources = ['Test\Entity2'];

        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2')
        ];
        $expectedAccessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true
        ];
        $expectedExcludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));

                    $context->setAccessibleResources(['Test\Entity2']);
                }
            );
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $accessibleResources
            );
        $this->resourcesCache->expects(self::at(3))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(4))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedExcludedActions);

        self::assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity2', $version, $requestType)
        );
        // test memory cache
        self::assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity2', $version, $requestType)
        );
    }

    public function testIsResourceKnownWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true
        ];
        $excludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::at(2))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');

        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity2', $version, $requestType)
        );
        // test memory cache
        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity2', $version, $requestType)
        );
    }

    public function testIsResourceKnownWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = ['Test\Entity2'];

        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2')
        ];
        $expectedAccessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true
        ];
        $expectedExcludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));

                    $context->setAccessibleResources(['Test\Entity2']);
                }
            );
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $accessibleResources
            );
        $this->resourcesCache->expects(self::at(3))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(4))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedExcludedActions);

        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity2', $version, $requestType)
        );
        // test memory cache
        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity2', $version, $requestType)
        );
    }

    public function testGetResourceExcludeActionsWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = ['Test\Entity1' => false, 'Test\Entity2' => true];
        $excludedActions = [
            'Test\Entity1' => [],
            'Test\Entity2' => ['delete']
        ];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::once())
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');

        self::assertSame(
            [],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
        self::assertSame(
            ['delete'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity2', $version, $requestType)
        );
        // test memory cache
        self::assertSame(
            [],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
        self::assertSame(
            ['delete'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity2', $version, $requestType)
        );
    }

    public function testGetResourceExcludeActionsWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1')];
        $accessibleResources = ['Test\Entity2'];

        $resource1 = new ApiResource('Test\Entity1');
        $resource3 = new ApiResource('Test\Entity2');
        $resource3->addExcludedAction('delete');
        $expectedResources = [$resource1, $resource3];
        $expectedAccessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true
        ];
        $expectedExcludedActions = [
            'Test\Entity2' => ['delete']
        ];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(1))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(2))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $resource1 = new ApiResource('Test\Entity1');
                    $context->getResult()->add($resource1);
                    $resource3 = new ApiResource('Test\Entity2');
                    $resource3->addExcludedAction('delete');
                    $context->getResult()->add($resource3);

                    $context->setAccessibleResources(['Test\Entity2']);
                }
            );
        $this->resourcesCache->expects(self::at(3))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $accessibleResources
            );
        $this->resourcesCache->expects(self::at(4))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(5))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($expectedExcludedActions);

        self::assertSame(
            [],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
        self::assertSame(
            ['delete'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity2', $version, $requestType)
        );
        // test memory cache
        self::assertSame(
            [],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
        self::assertSame(
            ['delete'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity2', $version, $requestType)
        );
    }

    public function testClearCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->resourcesCache->expects(self::exactly(2))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([new ApiResource('Test\Entity1')]);
        $this->resourcesCache->expects(self::exactly(2))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity1' => true]);
        $this->resourcesCache->expects(self::exactly(2))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity1' => ['update']]);
        $this->resourcesCache->expects(self::once())
            ->method('clear');

        // warmup the memory cache
        self::assertEquals(
            [new ApiResource('Test\Entity1')],
            $this->resourcesProvider->getResources($version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertEquals(
            ['update'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );

        // do cache clear, including the memory cache
        $this->resourcesProvider->clearCache();

        // check that clearCache method clears the memory cache
        self::assertEquals(
            [new ApiResource('Test\Entity1')],
            $this->resourcesProvider->getResources($version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertEquals(
            ['update'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
    }
}
