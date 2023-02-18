<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Processor\CollectResources\AddExcludedActions;
use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResourcesProcessor;
use Oro\Bundle\ApiBundle\Provider\ResourceCheckerInterface;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesWithoutIdentifierLoader;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ProcessorBagInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ResourcesProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CollectResourcesProcessor|\PHPUnit\Framework\MockObject\MockObject */
    private $processor;

    /** @var ResourcesCache|\PHPUnit\Framework\MockObject\MockObject */
    private $resourcesCache;

    /** @var ResourcesWithoutIdentifierLoader|\PHPUnit\Framework\MockObject\MockObject */
    private $resourcesWithoutIdentifierLoader;

    /** @var ResourceCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $resourceChecker;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    protected function setUp(): void
    {
        $this->processor = $this->getMockBuilder(CollectResourcesProcessor::class)
            ->onlyMethods(['process'])
            ->setConstructorArgs([$this->createMock(ProcessorBagInterface::class), 'collect_resources'])
            ->getMock();
        $this->resourcesCache = $this->createMock(ResourcesCache::class);
        $this->resourcesWithoutIdentifierLoader = $this->createMock(ResourcesWithoutIdentifierLoader::class);
        $this->resourceChecker = $this->createMock(ResourceCheckerInterface::class);

        $this->resourcesProvider = new ResourcesProvider(
            $this->processor,
            $this->resourcesCache,
            $this->resourcesWithoutIdentifierLoader,
            $this->resourceChecker
        );
    }

    public function testGetResourcesNoCache()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $expectedResourcesBeforeProcessingUpdateListAction = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2'),
            new ApiResource('Test\Entity3'),
            new ApiResource('Test\Entity4'),
            new ApiResource('Test\Entity5')
        ];
        $expectedResourcesWithoutIdentifier = ['Test\Entity3'];
        $expectedResourceWithoutIdentifier = new ApiResource('Test\Entity3');
        $expectedResourceWithoutIdentifier->addExcludedAction(ApiAction::UPDATE_LIST);
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            new ApiResource('Test\Entity2'),
            $expectedResourceWithoutIdentifier,
            new ApiResource('Test\Entity4'),
            new ApiResource('Test\Entity5')
        ];
        $expectedAccessibleResources = [
            'Test\Entity1' => 0,
            'Test\Entity2' => 3,
            'Test\Entity3' => 0,
            'Test\Entity4' => 1,
            'Test\Entity5' => 2
        ];
        $expectedExcludedActions = [
            'Test\Entity3' => [ApiAction::UPDATE_LIST]
        ];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::never())
            ->method('getAccessibleResources');
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CollectResourcesContext $context) use ($version, $requestType) {
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType, $context->getRequestType());

                $context->getResult()->add(new ApiResource('Test\Entity1'));
                $context->getResult()->add(new ApiResource('Test\Entity2'));
                $context->getResult()->add(new ApiResource('Test\Entity3'));
                $context->getResult()->add(new ApiResource('Test\Entity4'));
                $context->getResult()->add(new ApiResource('Test\Entity5'));

                $context->setAccessibleResources(['Test\Entity2', 'Test\Entity4']);
                $context->setAccessibleAsAssociationResources(['Test\Entity2', 'Test\Entity5']);

                $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
            });
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesWithoutIdentifierLoader->expects(self::once())
            ->method('load')
            ->with($version, self::identicalTo($requestType), $expectedResourcesBeforeProcessingUpdateListAction)
            ->willReturn($expectedResourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::once())
            ->method('saveResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType), $expectedResourcesWithoutIdentifier);
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
        // test memory cache
        self::assertEquals(
            $expectedResources,
            $this->resourcesProvider->getResources($version, $requestType)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
            'Test\Entity1' => 0,
            'Test\Entity2' => 0,
            'Test\Entity3' => 0,
            'Test\Entity4' => 0,
            'Test\Entity5' => 0
        ];
        $expectedExcludedActions = [
            'Test\Entity1' => [ApiAction::UPDATE_LIST],
            'Test\Entity2' => [ApiAction::UPDATE_LIST],
            'Test\Entity4' => [ApiAction::UPDATE_LIST],
            'Test\Entity5' => [ApiAction::UPDATE_LIST]
        ];

        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CollectResourcesContext $context) use ($version, $requestType) {
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType, $context->getRequestType());

                $context->getResult()->add(new ApiResource('Test\Entity1'));
                $context->getResult()->add(new ApiResource('Test\Entity2'));
                $context->getResult()->add(new ApiResource('Test\Entity3'));
                $context->getResult()->add(new ApiResource('Test\Entity4'));
                $context->getResult()->add(new ApiResource('Test\Entity5'));

                $context->setAccessibleResources([]);
                $context->setAccessibleAsAssociationResources([]);

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
            });
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetResourcesWhenGetAndGetListActionsAreExcluded()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $expectedExcludedGetAction = new ApiResource('Test\Entity2');
        $expectedExcludedGetAction->addExcludedAction(ApiAction::GET);
        $expectedExcludedGetAction->addExcludedAction(ApiAction::CREATE);
        $expectedExcludedGetAction->addExcludedAction(ApiAction::UPDATE);
        $expectedExcludedGetAction->addExcludedAction(ApiAction::DELETE);
        $expectedExcludedGetAction->addExcludedAction(ApiAction::UPDATE_LIST);
        $expectedExcludedGetAndGetListActions = new ApiResource('Test\Entity3');
        $expectedExcludedGetAndGetListActions->addExcludedAction(ApiAction::GET);
        $expectedExcludedGetAndGetListActions->addExcludedAction(ApiAction::GET_LIST);
        $expectedExcludedGetAndGetListActions->addExcludedAction(ApiAction::CREATE);
        $expectedExcludedGetAndGetListActions->addExcludedAction(ApiAction::UPDATE);
        $expectedExcludedGetAndGetListActions->addExcludedAction(ApiAction::DELETE);
        $expectedExcludedGetAndGetListActions->addExcludedAction(ApiAction::DELETE_LIST);
        $expectedExcludedGetAndGetListActions->addExcludedAction(ApiAction::UPDATE_LIST);
        $expectedExcludedGetActionButEnabledDeleteAction = new ApiResource('Test\Entity4');
        $expectedExcludedGetActionButEnabledDeleteAction->addExcludedAction(ApiAction::GET);
        $expectedExcludedGetActionButEnabledDeleteAction->addExcludedAction(ApiAction::CREATE);
        $expectedExcludedGetActionButEnabledDeleteAction->addExcludedAction(ApiAction::UPDATE);
        $expectedExcludedGetActionButEnabledDeleteAction->addExcludedAction(ApiAction::UPDATE_LIST);
        $expectedExcludedGetAndGetListActionsButEnabledDeleteListAction = new ApiResource('Test\Entity5');
        $expectedExcludedGetAndGetListActionsButEnabledDeleteListAction->addExcludedAction(ApiAction::GET);
        $expectedExcludedGetAndGetListActionsButEnabledDeleteListAction->addExcludedAction(ApiAction::GET_LIST);
        $expectedExcludedGetAndGetListActionsButEnabledDeleteListAction->addExcludedAction(ApiAction::CREATE);
        $expectedExcludedGetAndGetListActionsButEnabledDeleteListAction->addExcludedAction(ApiAction::UPDATE);
        $expectedExcludedGetAndGetListActionsButEnabledDeleteListAction->addExcludedAction(ApiAction::DELETE);
        $expectedExcludedGetAndGetListActionsButEnabledDeleteListAction->addExcludedAction(ApiAction::UPDATE_LIST);
        $expectedWithoutIdentifier = new ApiResource('Test\Entity6');
        $expectedWithoutIdentifier->addExcludedAction(ApiAction::UPDATE_LIST);
        $expectedResources = [
            new ApiResource('Test\Entity1'),
            $expectedExcludedGetAction,
            $expectedExcludedGetAndGetListActions,
            $expectedExcludedGetActionButEnabledDeleteAction,
            $expectedExcludedGetAndGetListActionsButEnabledDeleteListAction,
            $expectedWithoutIdentifier
        ];
        $expectedAccessibleResources = [
            'Test\Entity1' => 3,
            'Test\Entity2' => 3,
            'Test\Entity3' => 3,
            'Test\Entity4' => 3,
            'Test\Entity5' => 3,
            'Test\Entity6' => 1
        ];
        $expectedExcludedActions = [
            'Test\Entity2' => [
                ApiAction::GET,
                ApiAction::CREATE,
                ApiAction::UPDATE,
                ApiAction::DELETE,
                ApiAction::UPDATE_LIST
            ],
            'Test\Entity3' => [
                ApiAction::GET,
                ApiAction::GET_LIST,
                ApiAction::CREATE,
                ApiAction::UPDATE,
                ApiAction::DELETE,
                ApiAction::DELETE_LIST,
                ApiAction::UPDATE_LIST
            ],
            'Test\Entity4' => [
                ApiAction::GET,
                ApiAction::CREATE,
                ApiAction::UPDATE,
                ApiAction::UPDATE_LIST
            ],
            'Test\Entity5' => [
                ApiAction::GET,
                ApiAction::GET_LIST,
                ApiAction::CREATE,
                ApiAction::UPDATE,
                ApiAction::DELETE,
                ApiAction::UPDATE_LIST
            ],
            'Test\Entity6' => [ApiAction::UPDATE_LIST]
        ];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::never())
            ->method('getAccessibleResources');
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CollectResourcesContext $context) use ($version, $requestType) {
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType, $context->getRequestType());

                $context->getResult()->add(new ApiResource('Test\Entity1'));
                $excludedGetAction = new ApiResource('Test\Entity2');
                $excludedGetAction->addExcludedAction(ApiAction::GET);
                $context->getResult()->add($excludedGetAction);
                $excludedGetAndGetListActions = new ApiResource('Test\Entity3');
                $excludedGetAndGetListActions->addExcludedAction(ApiAction::GET);
                $excludedGetAndGetListActions->addExcludedAction(ApiAction::GET_LIST);
                $context->getResult()->add($excludedGetAndGetListActions);
                $excludedGetActionButEnabledDeleteAction = new ApiResource('Test\Entity4');
                $excludedGetActionButEnabledDeleteAction->addExcludedAction(ApiAction::GET);
                $context->getResult()->add($excludedGetActionButEnabledDeleteAction);
                $excludedGetAndGetListActionsButEnabledDeleteListAction = new ApiResource('Test\Entity5');
                $excludedGetAndGetListActionsButEnabledDeleteListAction->addExcludedAction(ApiAction::GET);
                $excludedGetAndGetListActionsButEnabledDeleteListAction->addExcludedAction(ApiAction::GET_LIST);
                $context->getResult()->add($excludedGetAndGetListActionsButEnabledDeleteListAction);
                $context->getResult()->add(new ApiResource('Test\Entity6'));

                $context->setAccessibleResources([
                    'Test\Entity1',
                    'Test\Entity2',
                    'Test\Entity3',
                    'Test\Entity4',
                    'Test\Entity5',
                    'Test\Entity6'
                ]);
                $context->setAccessibleAsAssociationResources([
                    'Test\Entity1',
                    'Test\Entity2',
                    'Test\Entity3',
                    'Test\Entity4',
                    'Test\Entity5'
                ]);

                $entity4Actions = new ActionsConfig();
                $entity4Actions->addAction(ApiAction::DELETE)->setExcluded(false);
                $entity5Actions = new ActionsConfig();
                $entity5Actions->addAction(ApiAction::DELETE_LIST)->setExcluded(false);
                $context->set(
                    AddExcludedActions::ACTIONS_CONFIG_KEY,
                    [
                        'Test\Entity4' => $entity4Actions,
                        'Test\Entity5' => $entity5Actions
                    ]
                );
            });
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([$expectedWithoutIdentifier->getEntityClass()]);
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

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
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
            'Test\Entity1' => 0,
            'Test\Entity2' => 1
        ];
        $excludedActions = [];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
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
        $this->resourcesCache->expects(self::once())
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
            'Test\Entity1' => 0,
            'Test\Entity2' => 3
        ];
        $expectedExcludedActions = [];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CollectResourcesContext $context) use ($version, $requestType) {
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType, $context->getRequestType());

                $context->getResult()->add(new ApiResource('Test\Entity1'));
                $context->getResult()->add(new ApiResource('Test\Entity2'));

                $context->setAccessibleResources(['Test\Entity2']);
                $context->setAccessibleAsAssociationResources(['Test\Entity2']);

                $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
            });
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);
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
        $accessibleResources = ['Test\Entity1' => 0];
        $excludedActions = [];
        $resourcesWithoutIdentifier = ['Test\Entity1'];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::once())
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->resourcesCache->expects(self::once())
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
            'Test\Entity1' => 0,
            'Test\Entity2' => 1
        ];
        $excludedActions = [];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
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
        $this->resourcesCache->expects(self::once())
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
            'Test\Entity1' => 0,
            'Test\Entity2' => 3
        ];
        $expectedExcludedActions = [];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CollectResourcesContext $context) use ($version, $requestType) {
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType, $context->getRequestType());

                $context->getResult()->add(new ApiResource('Test\Entity1'));
                $context->getResult()->add(new ApiResource('Test\Entity2'));

                $context->setAccessibleResources(['Test\Entity2']);
                $context->setAccessibleAsAssociationResources(['Test\Entity2']);

                $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
            });
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);
        $this->resourcesCache->expects(self::once())
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
        $accessibleResources = ['Test\Entity1' => 0];
        $excludedActions = [];
        $resourcesWithoutIdentifier = ['Test\Entity1'];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::once())
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->resourcesCache->expects(self::once())
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
            'Test\Entity1' => 0,
            'Test\Entity2' => 1
        ];
        $excludedActions = [];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
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
            'Test\Entity1' => 0,
            'Test\Entity2' => 3
        ];
        $expectedExcludedActions = [];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::never())
            ->method('getExcludedActions');
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CollectResourcesContext $context) use ($version, $requestType) {
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType, $context->getRequestType());

                $context->getResult()->add(new ApiResource('Test\Entity1'));
                $context->getResult()->add(new ApiResource('Test\Entity2'));

                $context->setAccessibleResources(['Test\Entity2']);
                $context->setAccessibleAsAssociationResources(['Test\Entity2']);

                $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
            });
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);
        $this->resourcesCache->expects(self::once())
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

    public function testIsResourceEnabled()
    {
        $action = 'get';
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->resourceChecker->expects(self::exactly(2))
            ->method('isResourceEnabled')
            ->willReturnMap([
                ['Test\Entity1', $action, $version, $requestType, true],
                ['Test\Entity2', $action, $version, $requestType, false]
            ]);

        self::assertTrue(
            $this->resourcesProvider->isResourceEnabled('Test\Entity1', $action, $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceEnabled('Test\Entity2', $action, $version, $requestType)
        );
        // test memory cache
        self::assertTrue(
            $this->resourcesProvider->isResourceEnabled('Test\Entity1', $action, $version, $requestType)
        );
        self::assertFalse(
            $this->resourcesProvider->isResourceEnabled('Test\Entity2', $action, $version, $requestType)
        );
    }

    public function testGetResourceExcludeActionsWhenCacheExists()
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $resources = [new ApiResource('Test\Entity1'), new ApiResource('Test\Entity2')];
        $accessibleResources = [
            'Test\Entity1' => 0,
            'Test\Entity2' => 1
        ];
        $excludedActions = [
            'Test\Entity1' => [],
            'Test\Entity2' => ['delete']
        ];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
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
        $accessibleResources = ['Test\Entity1' => 0];

        $resource1 = new ApiResource('Test\Entity1');
        $resource3 = new ApiResource('Test\Entity2');
        $resource3->addExcludedAction('delete');
        $expectedResources = [$resource1, $resource3];
        $expectedAccessibleResources = [
            'Test\Entity1' => 0,
            'Test\Entity2' => 3
        ];
        $expectedExcludedActions = [
            'Test\Entity2' => ['delete']
        ];

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::once())
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->processor->expects(self::once())
            ->method('process')
            ->willReturnCallback(function (CollectResourcesContext $context) use ($version, $requestType) {
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType, $context->getRequestType());

                $resource1 = new ApiResource('Test\Entity1');
                $context->getResult()->add($resource1);
                $resource3 = new ApiResource('Test\Entity2');
                $resource3->addExcludedAction('delete');
                $context->getResult()->add($resource3);

                $context->setAccessibleResources(['Test\Entity2']);
                $context->setAccessibleAsAssociationResources(['Test\Entity2']);

                $context->set(AddExcludedActions::ACTIONS_CONFIG_KEY, []);
            });
        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([]);
        $this->resourcesCache->expects(self::once())
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

    /**
     * @dataProvider isReadOnlyResourceDataProvider
     */
    public function testIsReadOnlyResource(bool $result, int $accessible, array $excludedActions)
    {
        $version = '1.2.3';
        $requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $entityClass = 'Test\Entity';

        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([new ApiResource($entityClass)]);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([$entityClass => $accessible]);
        $this->resourcesCache->expects(self::once())
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn([$entityClass => $excludedActions]);

        self::assertSame(
            $result,
            $this->resourcesProvider->isReadOnlyResource($entityClass, $version, $requestType)
        );
    }

    public function isReadOnlyResourceDataProvider(): array
    {
        return [
            'not accessible entity'                  => [
                'result'          => false,
                'accessible'      => 0,
                'excludedActions' => []
            ],
            'no excluded actions'                    => [
                'result'          => false,
                'accessible'      => 1,
                'excludedActions' => []
            ],
            'excluded all actions'                   => [
                'result'          => false,
                'accessible'      => 1,
                'excludedActions' => ['get', 'get_list', 'update', 'create', 'delete', 'delete_list']
            ],
            'excluded all "modify" actions'          => [
                'result'          => true,
                'accessible'      => 1,
                'excludedActions' => ['update', 'create', 'delete', 'delete_list']
            ],
            'excluded "update" and "create" actions' => [
                'result'          => true,
                'accessible'      => 1,
                'excludedActions' => ['update', 'create']
            ],
            'excluded "update" action'               => [
                'result'          => false,
                'accessible'      => 1,
                'excludedActions' => ['update']
            ],
            'excluded "create" action'               => [
                'result'          => false,
                'accessible'      => 1,
                'excludedActions' => ['create']
            ],
            'excluded "delete" actions'              => [
                'result'          => false,
                'accessible'      => 1,
                'excludedActions' => ['delete', 'delete_list']
            ],
            'excluded "get" and "get_list" action'   => [
                'result'          => false,
                'accessible'      => 1,
                'excludedActions' => ['get', 'get_list']
            ],
            'excluded "get" action'                  => [
                'result'          => false,
                'accessible'      => 1,
                'excludedActions' => ['get']
            ],
            'excluded "get_list" action'             => [
                'result'          => false,
                'accessible'      => 1,
                'excludedActions' => ['get_list']
            ],
        ];
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
        $accessibleResources = [
            'Test\Entity1' => 1,
            'Test\Entity2' => 1
        ];
        $excludedActions = [];

        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::once())
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->resourcesWithoutIdentifierLoader->expects(self::once())
            ->method('load')
            ->with($version, self::identicalTo($requestType), $resources)
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::once())
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
        $accessibleResources = ['Test\Entity1' => 1];
        $excludedActions = [];

        $this->resourcesCache->expects(self::once())
            ->method('getResourcesWithoutIdentifier')
            ->with($version, self::identicalTo($requestType))
            ->willReturn(null);
        $this->resourcesCache->expects(self::once())
            ->method('getResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($resources);
        $this->resourcesCache->expects(self::once())
            ->method('getAccessibleResources')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($accessibleResources);
        $this->resourcesCache->expects(self::once())
            ->method('getExcludedActions')
            ->with($version, self::identicalTo($requestType))
            ->willReturn($excludedActions);
        $this->resourcesWithoutIdentifierLoader->expects(self::once())
            ->method('load')
            ->with($version, self::identicalTo($requestType), $resources)
            ->willReturn($resourcesWithoutIdentifier);
        $this->resourcesCache->expects(self::once())
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
        $action = 'get';
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
        $this->resourceChecker->expects(self::exactly(2))
            ->method('isResourceEnabled')
            ->with('Test\Entity1', $action, $version, $requestType)
            ->willReturn(true);

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
        self::assertTrue(
            $this->resourcesProvider->isResourceEnabled('Test\Entity1', $action, $version, $requestType)
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
        self::assertTrue(
            $this->resourcesProvider->isResourceEnabled('Test\Entity1', $action, $version, $requestType)
        );
    }

    public function testReset()
    {
        $action = 'get';
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
        $this->resourceChecker->expects(self::exactly(2))
            ->method('isResourceEnabled')
            ->with('Test\Entity1', $action, $version, $requestType)
            ->willReturn(true);

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
        self::assertTrue(
            $this->resourcesProvider->isResourceEnabled('Test\Entity1', $action, $version, $requestType)
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
        self::assertTrue(
            $this->resourcesProvider->isResourceEnabled('Test\Entity1', $action, $version, $requestType)
        );
    }
}
