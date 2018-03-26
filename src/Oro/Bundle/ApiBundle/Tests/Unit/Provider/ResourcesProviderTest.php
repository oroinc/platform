<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResourcesProcessor;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesWithoutIdentifierLoader;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

class ResourcesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|CollectResourcesProcessor */
    private $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ResourcesCache */
    private $resourcesCache;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ResourcesWithoutIdentifierLoader */
    private $resourcesWithoutIdentifierLoader;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    protected function setUp()
    {
        $this->processor = $this->createMock(CollectResourcesProcessor::class);
        $this->resourcesCache = $this->createMock(ResourcesCache::class);
        $this->resourcesWithoutIdentifierLoader = $this->createMock(ResourcesWithoutIdentifierLoader::class);

        $this->resourcesProvider = new ResourcesProvider(
            $this->processor,
            $this->resourcesCache,
            $this->resourcesWithoutIdentifierLoader
        );
    }

    public function testGetResourcesNoCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::once())
            ->method('saveResources')
            ->with($version, self::identicalTo($requestType), $expectedResources, $expectedAccessibleResources);

        self::assertEquals(
            $expectedResources,
            $this->resourcesProvider->getResources($version, $requestType)
        );
    }

    public function testGetResourcesFromCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];

        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedResources);
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');

        self::assertEquals(
            $cachedResources,
            $this->resourcesProvider->getResources($version, $requestType)
        );
    }

    public function testGetAccessibleResourcesWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];

        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);

        self::assertEquals(
            ['Test\Entity3'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
    }

    public function testGetAccessibleResourcesWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects(self::at(0))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(1))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResources')
            ->with($version, self::identicalTo($requestType), $expectedResources, $expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(3))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);

        self::assertEquals(
            ['Test\Entity3'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
    }

    public function testGetAccessibleResourcesForNotAccessibleResourceWithoutIdentifier()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity1' => false]);
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity1']);

        self::assertEquals(
            ['Test\Entity1'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
    }

    public function testIsResourceAccessibleWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];

        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);

        self::assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity2', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity3', $version, $requestType)
        );
    }

    public function testIsResourceAccessibleWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects(self::at(0))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(1))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResources')
            ->with($version, self::identicalTo($requestType), $expectedResources, $expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(3))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);

        self::assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceAccessible('Test\Entity2', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity3', $version, $requestType)
        );
    }

    public function testIsResourceAccessibleForNotAccessibleResourceWithoutIdentifier()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity1' => false]);
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity1']);

        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
    }

    public function testIsResourceKnownWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];

        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');

        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity1', $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceKnown('Test\Entity2', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity3', $version, $requestType)
        );
    }

    public function testIsResourceKnownWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => false,
            'Test\Entity3' => true
        ];
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity3'),
        ];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects(self::at(0))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(1))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResources')
            ->with($version, self::identicalTo($requestType), $expectedResources, $expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(3))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedData);

        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity1', $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceKnown('Test\Entity2', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceKnown('Test\Entity3', $version, $requestType)
        );
    }

    public function testGetResourceExcludeActionsWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => [],
            'Test\Entity3' => ['delete']
        ];

        $this->processor->expects(self::never())
            ->method('process');
        $this->resourcesCache->expects(self::once())
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedData);
        $this->resourcesCache->expects(self::never())
            ->method('saveResources');

        self::assertSame(
            [],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
        self::assertSame(
            [],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity2', $version, $requestType)
        );
        self::assertSame(
            ['delete'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity3', $version, $requestType)
        );
    }

    public function testGetResourceExcludeActionsWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $cachedData = [
            'Test\Entity1' => [],
            'Test\Entity3' => ['delete']
        ];
        $resource1 = new ApiResource('Test\Entity1');
        $resource3 = new ApiResource('Test\Entity3');
        $resource3->addExcludedAction('delete');
        $expectedResources = [$resource1, $resource3];
        $expectedAccessibleResources = ['Test\Entity3'];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $resource1 = new ApiResource('Test\Entity1');
                    $context->getResult()->add($resource1);
                    $resource3 = new ApiResource('Test\Entity3');
                    $resource3->addExcludedAction('delete');
                    $context->getResult()->add($resource3);

                    $context->setAccessibleResources(['Test\Entity3']);
                }
            );
        $this->resourcesCache->expects(self::at(0))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(1))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResources')
            ->with($version, self::identicalTo($requestType), $expectedResources, $expectedAccessibleResources);
        $this->resourcesCache->expects(self::at(3))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($cachedData);

        self::assertSame(
            [],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
        self::assertSame(
            [],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity2', $version, $requestType)
        );
        self::assertSame(
            ['delete'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity3', $version, $requestType)
        );
    }

    public function testGetResourcesWithoutIdentifierWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resourcesWithoutIdentifier = ['Test\Entity1', 'Test\Entity2'];

        $this->resourcesCache->expects(self::never())
            ->method('getResources');
        $this->resourcesWithoutIdentifierLoader->expects(self::never())
            ->method('load');
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::never())
            ->method('saveResourcesWithoutIdentifier');

        self::assertEquals(
            $resourcesWithoutIdentifier,
            $this->resourcesProvider->getResourcesWithoutIdentifier($version, $requestType)
        );
        // test local cache
        self::assertEquals(
            $resourcesWithoutIdentifier,
            $this->resourcesProvider->getResourcesWithoutIdentifier($version, $requestType)
        );
    }

    public function testGetResourcesWithoutIdentifierWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resourcesWithoutIdentifier = ['Test\Entity1', 'Test\Entity2'];
        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesWithoutIdentifierLoader->expects(self::once())
            ->method('load')
            ->with($version, self::identicalTo($requestType), $resources)
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::at(0))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType), $resourcesWithoutIdentifier);

        self::assertEquals(
            $resourcesWithoutIdentifier,
            $this->resourcesProvider->getResourcesWithoutIdentifier($version, $requestType)
        );
        // test local cache
        self::assertEquals(
            $resourcesWithoutIdentifier,
            $this->resourcesProvider->getResourcesWithoutIdentifier($version, $requestType)
        );
    }

    public function testIsResourceWithoutIdentifierWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resourcesWithoutIdentifier = ['Test\Entity1'];

        $this->resourcesCache->expects(self::never())
            ->method('getResources');
        $this->resourcesWithoutIdentifierLoader->expects(self::never())
            ->method('load');
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::never())
            ->method('saveResourcesWithoutIdentifier');

        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity1', $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );
    }

    public function testIsResourceWithoutIdentifierWhenCacheDoesNotExist()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resourcesWithoutIdentifier = ['Test\Entity1'];
        $resources = [new ApiResource('Test\Entity1')];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesWithoutIdentifierLoader->expects(self::once())
            ->method('load')
            ->with($version, self::identicalTo($requestType), $resources)
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::at(0))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType), $resourcesWithoutIdentifier);

        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity1', $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );
    }

    public function testClearCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->resourcesCache->expects(self::exactly(2))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity1' => true]);
        $this->resourcesCache->expects(self::exactly(2))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity1' => ['update']]);
        $this->resourcesCache->expects(self::exactly(2))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity2']);
        $this->resourcesCache->expects(self::once())
            ->method('clear');

        // warmup the local cache
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertEquals(
            ['update'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );

        // do cache clear, including the local cache
        $this->resourcesProvider->clearCache();

        // check that clearCache method clears the local cache
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        self::assertEquals(
            ['update'],
            $this->resourcesProvider->getResourceExcludeActions('Test\Entity1', $version, $requestType)
        );
        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );
    }
}
