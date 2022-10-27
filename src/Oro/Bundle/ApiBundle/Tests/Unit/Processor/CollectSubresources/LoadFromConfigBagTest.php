<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Loader\ActionsConfigLoader;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\Loader\SubresourcesConfigLoader;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\CollectSubresources\CollectSubresourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectSubresources\LoadFromConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBagRegistry;
use Oro\Bundle\ApiBundle\Provider\ConfigCache;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresources;
use Oro\Bundle\ApiBundle\Request\ApiResourceSubresourcesCollection;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LoadFromConfigBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigBagRegistry */
    private $configBagRegistry;

    /** @var CollectSubresourcesContext */
    private $context;

    /** @var LoadFromConfigBag */
    private $processor;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);
        $this->configBagRegistry = $this->createMock(ConfigBagRegistry::class);

        $configLoaderFactory = $this->createMock(ConfigLoaderFactory::class);
        $configLoaderFactory->expects(self::any())
            ->method('getLoader')
            ->willReturnMap([
                [ConfigUtil::SUBRESOURCES, new SubresourcesConfigLoader()],
                [ConfigUtil::ACTIONS, new ActionsConfigLoader()]
            ]);

        $this->context = new CollectSubresourcesContext();
        $this->context->getRequestType()->add(RequestType::REST);
        $this->context->setVersion('1.1');

        $this->processor = new LoadFromConfigBag(
            $configLoaderFactory,
            $this->configBagRegistry,
            $this->configProvider,
            $this->metadataProvider
        );
    }

    private function getApiResourceSubresources(ApiResource $resource): ApiResourceSubresourcesCollection
    {
        $entitySubresources = new ApiResourceSubresources($resource->getEntityClass());
        $subresources = new ApiResourceSubresourcesCollection();
        $subresources->add($entitySubresources);

        return $subresources;
    }

    private function expectGetConfigBag(ConfigBag $configBag)
    {
        $this->configBagRegistry->expects(self::once())
            ->method('getConfigBag')
            ->with($this->context->getRequestType())
            ->willReturn($configBag);
    }

    private function expectGetConfig(string $entityClass)
    {
        $config = new Config();
        $config->setDefinition(new EntityDefinitionConfig());

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra()]
            )
            ->willReturn($config);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');
    }

    private function expectGetMetadata(string $entityClass, EntityMetadata $entityMetadata)
    {
        $config = new Config();
        $config->setDefinition(new EntityDefinitionConfig());

        if (count($entityMetadata->getAssociations()) !== 0) {
            $associations = $entityMetadata->getAssociations();
            $association = reset($associations);
            $field = $config->getDefinition()->addField($association->getName());
            if ($association->getPropertyPath() !== $association->getName()) {
                $field->setPropertyPath($association->getPropertyPath());
            }
        }

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [new EntityDefinitionConfigExtra()]
            )
            ->willReturn($config);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $config->getDefinition(),
                [],
                true
            )
            ->willReturn($entityMetadata);
    }

    public function testProcessDisabledSubresources()
    {
        $entityClass = 'Test\Class';
        $resource = new ApiResource($entityClass);
        $resource->setExcludedActions([ApiAction::GET_SUBRESOURCE]);
        $subresources = $this->getApiResourceSubresources($resource);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);

        $this->configBagRegistry->expects(self::never())
            ->method('getConfigBag');

        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessResourceWithoutSubresourceConfigs()
    {
        $entityClass = 'Test\Class';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => []
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessResourceWithoutEntityConfig()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'subresource1' => [
                                'target_class' => $targetEntityClass,
                                'target_type'  => 'to-one',
                                'actions'      => [ApiAction::UPDATE_SUBRESOURCE => ['exclude' => false]]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetConfig($entityClass);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([$targetEntityClass]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('subresource1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessExcludedSubresource()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);
        $entityMetadata = new EntityMetadata('Test\Entity');
        $association = $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $association->setTargetClassName($targetEntityClass);
        $association->setAcceptableTargetClassNames([$targetEntityClass]);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'association1' => [
                                'exclude' => true,
                                'actions' => [ApiAction::UPDATE_SUBRESOURCE => ['exclude' => false]]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([$targetEntityClass]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessSubresourceCreatedBasedOnAssociation()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);
        $entityMetadata = new EntityMetadata('Test\Entity');
        $association = $entityMetadata->addAssociation(new AssociationMetadata('association1'));
        $association->setTargetClassName($targetEntityClass);
        $association->setAcceptableTargetClassNames([$targetEntityClass]);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'association1' => [
                                'actions' => [ApiAction::UPDATE_SUBRESOURCE => ['exclude' => false]]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetMetadata($entityClass, $entityMetadata);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([$targetEntityClass]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('association1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessSubresourceCreatedBasedOnRenamedAssociation()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);
        $entityMetadata = new EntityMetadata('Test\Entity');
        $association = $entityMetadata->addAssociation(new AssociationMetadata('renamedAssociation1'));
        $association->setPropertyPath('association1');
        $association->setTargetClassName($targetEntityClass);
        $association->setAcceptableTargetClassNames([$targetEntityClass]);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'association1' => [
                                'actions' => [ApiAction::UPDATE_SUBRESOURCE => ['exclude' => false]]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetMetadata($entityClass, $entityMetadata);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([$targetEntityClass]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('association1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessCustomSubresource()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'subresource1' => [
                                'target_class' => $targetEntityClass,
                                'target_type'  => 'to-one',
                                'actions'      => [ApiAction::UPDATE_SUBRESOURCE => ['description' => 'test']]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetConfig($entityClass);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([$targetEntityClass]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('subresource1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessCustomSubresourceWithUnaccessibleTarget()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'subresource1' => [
                                'target_class' => $targetEntityClass,
                                'target_type'  => 'to-one',
                                'actions'      => [
                                    ApiAction::ADD_SUBRESOURCE    => ['description' => 'test'],
                                    ApiAction::UPDATE_SUBRESOURCE => ['exclude' => false],
                                    ApiAction::DELETE_SUBRESOURCE => ['exclude' => true]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetConfig($entityClass);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::GET_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('subresource1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessCustomSubresourceWithoutTargetClass()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Invalid configuration for "subresource1" subresource of "Test\Class" entity'
            . '. The target class should be specified in config.'
        );

        $entityClass = 'Test\Class';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'subresource1' => [
                                'actions' => [ApiAction::UPDATE_SUBRESOURCE => ['exclude' => false]]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetConfig($entityClass);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);
    }

    public function testProcessCustomSubresourceWithEntityIdentifierAndNoAcceptableTargetClassNames()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = EntityIdentifier::class;
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'subresource1' => [
                                'target_class' => $targetEntityClass,
                                'target_type'  => 'to-one'
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetConfig($entityClass);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setAcceptableTargetClassNames([]);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::UPDATE_SUBRESOURCE,
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('subresource1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessCustomSubresourceWhenItIsExcluded()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'subresources' => [
                            'subresource1' => [
                                'target_class' => $targetEntityClass,
                                'target_type'  => 'to-one',
                                'actions'      => [ApiAction::UPDATE_SUBRESOURCE => ['exclude' => true]]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetConfig($entityClass);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([$targetEntityClass]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::UPDATE_SUBRESOURCE,
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('subresource1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessCustomSubresourceWhenItsActionIsExcludedOnEntityLevel()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'actions'      => [
                            ApiAction::UPDATE_SUBRESOURCE => ['exclude' => true]
                        ],
                        'subresources' => [
                            'subresource1' => [
                                'target_class' => $targetEntityClass,
                                'target_type'  => 'to-one',
                                'actions'      => [ApiAction::UPDATE_SUBRESOURCE => ['description' => 'test']]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetConfig($entityClass);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([$targetEntityClass]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::UPDATE_SUBRESOURCE,
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('subresource1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }

    public function testProcessCustomSubresourceWhenSomeActionsAreExcludedOnEntityLevelButNotActionForThisSubresource()
    {
        $entityClass = 'Test\Class';
        $targetEntityClass = 'Test\TargetClass';
        $resource = new ApiResource($entityClass);
        $subresources = $this->getApiResourceSubresources($resource);

        $configFile = 'api.yml';
        $configCache = $this->createMock(ConfigCache::class);
        $configCache->expects(self::once())
            ->method('getConfig')
            ->with($configFile)
            ->willReturn([
                'entities' => [
                    $entityClass => [
                        'actions'      => [
                            ApiAction::CREATE => ['exclude' => true]
                        ],
                        'subresources' => [
                            'subresource1' => [
                                'target_class' => $targetEntityClass,
                                'target_type'  => 'to-one',
                                'actions'      => [ApiAction::UPDATE_SUBRESOURCE => ['description' => 'test']]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->expectGetConfigBag(new ConfigBag($configCache, $configFile));
        $this->expectGetConfig($entityClass);

        $this->context->setResources([$resource]);
        $this->context->setAccessibleResources([$targetEntityClass]);
        $this->context->setResult($subresources);
        $this->processor->process($this->context);

        $expectedSubresources = new ApiResourceSubresources($entityClass);
        $expectedSubresource = new ApiSubresource();
        $expectedSubresource->setTargetClassName($targetEntityClass);
        $expectedSubresource->setIsCollection(false);
        $expectedSubresource->setExcludedActions([
            ApiAction::ADD_SUBRESOURCE,
            ApiAction::DELETE_SUBRESOURCE,
            ApiAction::GET_RELATIONSHIP,
            ApiAction::UPDATE_RELATIONSHIP,
            ApiAction::ADD_RELATIONSHIP,
            ApiAction::DELETE_RELATIONSHIP
        ]);
        $expectedSubresources->addSubresource('subresource1', $expectedSubresource);

        self::assertEquals(
            [$entityClass => $expectedSubresources],
            $this->context->getResult()->toArray()
        );
    }
}
