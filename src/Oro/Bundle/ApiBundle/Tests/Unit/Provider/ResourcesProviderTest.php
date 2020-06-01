<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Processor\CollectResources\AddExcludedActions;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResourcesProcessor;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesWithoutIdentifierLoader;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ResourcesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CollectResourcesProcessor */
    private $processor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesCache */
    private $resourcesCache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesWithoutIdentifierLoader */
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

        $expectedResourcesBeforeProcessingUpdateListAction = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2'),
            new ApiResource('Test\Entity3')
        ];
        $expectedResourcesWithoutIdentifier = ['Test\Entity3'];
        $expectedResourceWithoutIdentifier = new ApiResource('Test\Entity3');
        $expectedResourceWithoutIdentifier->addExcludedAction(ApiAction::UPDATE_LIST);
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2'),
            $expectedResourceWithoutIdentifier
        ];
        $expectedAccessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => true,
            'Test\Entity3' => true
        ];
        $expectedExcludedActions = [
            'Test\Entity3' => [ApiAction::UPDATE_LIST]
        ];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::never())
            ->method('getAccessibleResources');
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));

                    $context->setAccessibleResources(['Test\Entity2', 'Test\Entity3']);

                    $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
                }
            );
        $this->resourcesCache->expects(self::at(1))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesWithoutIdentifierLoader->expects(self::once())
            ->method('load')
            ->with($version, self::identicalTo($requestType), $expectedResourcesBeforeProcessingUpdateListAction)
            ->willReturn($expectedResourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::at(2))
            ->method('saveResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType), $expectedResourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::at(3))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $expectedAccessibleResources,
                $expectedExcludedActions
            );

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

    public function testGetResourcesNoCacheButWithCachedResourcesWithoutIdentifier()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $entity1Resource = new ApiResource('Test\Entity1');
        $entity1Resource->addExcludedAction(ApiAction::UPDATE_LIST);
        $entity2Resource = new ApiResource('Test\Entity2');
        $entity2Resource->addExcludedAction(ApiAction::UPDATE_LIST);
        $entity3Resource = new ApiResource('Test\Entity3');
        $entity4Resource = new ApiResource('Test\Entity4');
        $entity4Resource->addExcludedAction(ApiAction::UPDATE_LIST);
        $entity5Resource = new ApiResource('Test\Entity5');
        $entity5Resource->addExcludedAction(ApiAction::UPDATE_LIST);
        $expectedResources = [
            $entity1Resource,
            $entity2Resource,
            $entity3Resource,
            $entity4Resource,
            $entity5Resource
        ];
        $expectedAccessibleResources = [
            'Test\Entity1' => false,
            'Test\Entity2' => false,
            'Test\Entity3' => false,
            'Test\Entity4' => false,
            'Test\Entity5' => false
        ];
        $expectedExcludedActions = [
            'Test\Entity1' => [ApiAction::UPDATE_LIST],
            'Test\Entity2' => [ApiAction::UPDATE_LIST],
            'Test\Entity4' => [ApiAction::UPDATE_LIST],
            'Test\Entity5' => [ApiAction::UPDATE_LIST]
        ];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));
                    $context->getResult()->add(new ApiResource('Test\Entity3'));
                    $context->getResult()->add(new ApiResource('Test\Entity4'));
                    $context->getResult()->add(new ApiResource('Test\Entity5'));

                    $context->setAccessibleResources([]);

                    $entity2Actions = new ActionsConfig();
                    $entity2Actions->addAction(ApiAction::UPDATE_LIST)->setExcluded();
                    $entity3Actions = new ActionsConfig();
                    $entity3Actions->addAction(ApiAction::UPDATE_LIST)->setExcluded(false);
                    $entity4Actions = new ActionsConfig();
                    $entity4Actions->addAction(ApiAction::UPDATE_LIST);
                    $entity5Actions = new ActionsConfig();
                    $entity5Actions->addAction(ApiAction::CREATE);
                    $entity5Actions->addAction(ApiAction::GET)->setExcluded();
                    $context->set(
                        AddExcludedActions::ACTIONS_CONFIG_KEY,
                        [
                            'Test\Entity2' => $entity2Actions,
                            'Test\Entity3' => $entity3Actions,
                            'Test\Entity4' => $entity4Actions,
                            'Test\Entity5' => $entity5Actions
                        ]
                    );
                }
            );
        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([
                'Test\Entity1',
                'Test\Entity2',
                'Test\Entity3',
                'Test\Entity4',
                'Test\Entity5'
            ]);
        $this->resourcesCache->expects(self::once())
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $expectedAccessibleResources,
                $expectedExcludedActions
            );

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
        $this->resourcesCache->expects(self::at(3))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);

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
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));

                    $context->setAccessibleResources(['Test\Entity2']);

                    $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
                }
            );
        $this->resourcesCache->expects(self::at(2))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);
        $this->resourcesCache->expects(self::at(3))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $expectedAccessibleResources,
                $expectedExcludedActions
            );

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

    public function testGetAccessibleResourcesForNotAccessibleResourceWithoutIdentifier()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = ['Test\Entity1' => false];
        $excludedActions = [];
        $resourcesWithoutIdentifier = ['Test\Entity1'];

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
        $this->resourcesCache->expects(self::at(3))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resourcesWithoutIdentifier);

        self::assertEquals(
            ['Test\Entity1'],
            $this->resourcesProvider->getAccessibleResources($version, $requestType)
        );
        // test memory cache
        self::assertEquals(
            ['Test\Entity1'],
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
        $this->resourcesCache->expects(self::at(3))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);

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
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));

                    $context->setAccessibleResources(['Test\Entity2']);

                    $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
                }
            );
        $this->resourcesCache->expects(self::at(2))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);
        $this->resourcesCache->expects(self::at(3))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $expectedAccessibleResources,
                $expectedExcludedActions
            );

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

    public function testIsResourceAccessibleForNotAccessibleResourceWithoutIdentifier()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = ['Test\Entity1' => false];
        $excludedActions = [];
        $resourcesWithoutIdentifier = ['Test\Entity1'];

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
        $this->resourcesCache->expects(self::at(3))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resourcesWithoutIdentifier);

        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
        );
        // test memory cache
        self::assertTrue(
            $this->resourcesProvider->isResourceAccessible('Test\Entity1', $version, $requestType)
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
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(
                function (CollectResourcesContext $context) use ($version, $requestType) {
                    self::assertEquals($version, $context->getVersion());
                    self::assertEquals($requestType, $context->getRequestType());

                    $context->getResult()->add(new ApiResource('Test\Entity1'));
                    $context->getResult()->add(new ApiResource('Test\Entity2'));

                    $context->setAccessibleResources(['Test\Entity2']);

                    $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
                }
            );
        $this->resourcesCache->expects(self::at(2))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);
        $this->resourcesCache->expects(self::at(3))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $expectedAccessibleResources,
                $expectedExcludedActions
            );

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
        $accessibleResources = ['Test\Entity1' => true];

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
            ->willReturn($accessibleResources);
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

                    $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
                }
            );
        $this->resourcesCache->expects(self::at(3))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);
        $this->resourcesCache->expects(self::at(4))
            ->method('saveResources')
            ->with(
                $version,
                self::identicalTo($requestType),
                $expectedResources,
                $expectedAccessibleResources,
                $expectedExcludedActions
            );

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
        // test memory cache
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
        $accessibleResources = ['Test\Entity1' => true, 'Test\Entity2' => true];
        $excludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(1))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(2))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::at(3))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->resourcesWithoutIdentifierLoader->expects(self::once())
            ->method('load')
            ->with($version, self::identicalTo($requestType), $resources)
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::at(4))
            ->method('saveResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType), $resourcesWithoutIdentifier);

        self::assertEquals(
            $resourcesWithoutIdentifier,
            $this->resourcesProvider->getResourcesWithoutIdentifier($version, $requestType)
        );
        // test memory cache
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

        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::never())
            ->method('getResources');
        $this->resourcesWithoutIdentifierLoader->expects(self::never())
            ->method('load');
        $this->resourcesCache->expects(self::never())
            ->method('saveResourcesWithoutIdentifier');

        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity1', $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );
        // test memory cache
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
        $accessibleResources = ['Test\Entity1' => true];
        $excludedActions = [];

        $this->resourcesCache->expects(self::at(0))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::at(1))
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::at(2))
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::at(3))
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->resourcesWithoutIdentifierLoader->expects(self::once())
            ->method('load')
            ->with($version, self::identicalTo($requestType), $resources)
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::at(4))
            ->method('saveResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType), $resourcesWithoutIdentifier);

        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity1', $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );
        // test memory cache
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
        $this->resourcesCache->expects(self::exactly(2))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity2']);
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
        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
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
        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );
    }

    public function testReset()
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
        $this->resourcesCache->expects(self::exactly(2))
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(['Test\Entity2']);
        $this->resourcesCache->expects(self::never())
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
        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );

        // clear the memory cache
        $this->resourcesProvider->reset();

        // test that the memory cache was cleared
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
        self::assertTrue(
            $this->resourcesProvider->isResourceWithoutIdentifier('Test\Entity2', $version, $requestType)
        );
    }
}
