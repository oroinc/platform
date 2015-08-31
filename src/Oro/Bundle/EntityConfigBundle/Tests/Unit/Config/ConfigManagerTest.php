<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\EntityConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity';

    /** @var ConfigManager */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var ConfigProviderBag */
    protected $configProviderBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $modelManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $auditManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configCache;

    protected function setUp()
    {
        $this->configProvider = $this->getConfigProviderMock();
        $this->configProvider->expects($this->any())
            ->method('getScope')
            ->willReturn('entity');

        $this->configProviderBag = new ConfigProviderBag();
        $this->configProviderBag->addProvider($this->configProvider);
        $this->container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('has')
            ->willReturnCallback(
                function ($id) {
                    switch ($id) {
                        case 'ConfigProviderBag':
                            return true;
                        default:
                            return false;
                    }
                }
            );
        $configProviderBag = $this->configProviderBag;
        $this->container->expects($this->any())
            ->method('get')
            ->willReturnCallback(
                function ($id) use (&$configProviderBag) {
                    switch ($id) {
                        case 'ConfigProviderBag':
                            return $configProviderBag;
                        default:
                            return null;
                    }
                }
            );

        $this->metadataFactory = $this->getMockBuilder('Metadata\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->modelManager    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->auditManager    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Audit\AuditManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configCache     = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = new ConfigManager(
            $this->metadataFactory,
            $this->eventDispatcher,
            new ServiceLink($this->container, 'ConfigProviderBag'),
            $this->modelManager,
            $this->auditManager,
            $this->configCache
        );
    }

    public function testGetProviderBag()
    {
        $this->assertTrue($this->configManager->getProviderBag() === $this->configProviderBag);
    }

    public function testGetProviders()
    {
        $providers = $this->configManager->getProviders();
        $this->assertCount(1, $providers);
        $this->assertSame($this->configProvider, $providers['entity']);
    }

    public function testGetProvider()
    {
        $this->assertSame($this->configProvider, $this->configManager->getProvider('entity'));
    }

    public function testGetEventDispatcher()
    {
        $this->assertSame($this->eventDispatcher, $this->configManager->getEventDispatcher());
    }

    public function testGetEntityMetadata()
    {
        $this->assertNull($this->configManager->getEntityMetadata('SomeUndefinedClass'));

        $metadata = $this->getEntityMetadata(self::ENTITY_CLASS);

        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);

        $this->assertSame($metadata, $this->configManager->getEntityMetadata(self::ENTITY_CLASS));
    }

    public function testGetFieldMetadata()
    {
        $this->assertNull($this->configManager->getFieldMetadata('SomeUndefinedClass', 'entity'));

        $metadata        = $this->getEntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = $this->getFieldMetadata(self::ENTITY_CLASS, 'id');
        $metadata->addPropertyMetadata($idFieldMetadata);

        $this->metadataFactory->expects($this->exactly(2))
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);

        $this->assertNull($this->configManager->getFieldMetadata(self::ENTITY_CLASS, 'undefinedField'));

        $this->assertSame(
            $metadata->propertyMetadata['id'],
            $this->configManager->getFieldMetadata(self::ENTITY_CLASS, 'id')
        );
    }

    /**
     * @dataProvider hasConfigProvider
     */
    public function testHasConfig(
        $expectedResult,
        $checkDatabaseResult,
        $cachedResult,
        $findModelResult,
        $className,
        $fieldName
    ) {
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->willReturn($checkDatabaseResult);
        if ($checkDatabaseResult) {
            $this->configCache->expects($this->once())
                ->method('getConfigurable')
                ->with($className, $fieldName)
                ->willReturn($cachedResult);
            if (null === $cachedResult) {
                $this->configCache->expects($this->once())
                    ->method('saveConfigurable')
                    ->with($expectedResult, $className, $fieldName);
                if ($fieldName) {
                    $this->modelManager->expects($this->once())
                        ->method('findFieldModel')
                        ->with($className, $fieldName)
                        ->willReturn($findModelResult);
                } else {
                    $this->modelManager->expects($this->once())
                        ->method('findEntityModel')
                        ->with($className)
                        ->willReturn($findModelResult);
                }
            }
        }

        $result = $this->configManager->hasConfig($className, $fieldName);
        $this->assertEquals($expectedResult, $result);
    }

    public function hasConfigProvider()
    {
        return [
            'no database'          => [false, false, null, null, self::ENTITY_CLASS, null],
            'no database (field)'  => [false, false, null, null, self::ENTITY_CLASS, 'id'],
            'cached false'         => [false, true, false, null, self::ENTITY_CLASS, null],
            'cached false (field)' => [false, true, false, null, self::ENTITY_CLASS, 'id'],
            'cached true'          => [true, true, true, null, self::ENTITY_CLASS, null],
            'cached true (field)'  => [true, true, true, null, self::ENTITY_CLASS, 'id'],
            'no model'             => [false, true, null, null, self::ENTITY_CLASS, null],
            'no model (field)'     => [false, true, null, null, self::ENTITY_CLASS, 'id'],
            'has model'            => [
                true,
                true,
                null,
                $this->createEntityConfigModel(self::ENTITY_CLASS),
                self::ENTITY_CLASS,
                null
            ],
            'has model (field)'    => [
                true,
                true,
                null,
                $this->createFieldConfigModel(
                    $this->createEntityConfigModel(self::ENTITY_CLASS),
                    'id',
                    'int'
                ),
                self::ENTITY_CLASS,
                'id'
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\LogicException
     */
    public function testGetConfigNoDatabase()
    {
        $configId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->willReturn(false);
        $this->configManager->getConfig($configId);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     */
    public function testGetConfigForNotConfigurable()
    {
        $configId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->willReturn(true);
        $this->configCache->expects($this->once())
            ->method('getConfigurable')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);
        $this->configManager->getConfig($configId);
    }

    public function testGetConfigForNewEntity()
    {
        $configId = new EntityConfigId('entity');

        $this->modelManager->expects($this->never())
            ->method('checkDatabase');
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable' => 'labelVal', 'other' => 'otherVal']);
        $propertyConfigContainer->expects($this->never())
            ->method('getTranslatableValues');
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);

        $config = $this->configManager->getConfig($configId);

        $expectedConfig = $this->getConfig(
            $configId,
            [
                'translatable' => 'labelVal',
                'other'        => 'otherVal'
            ]
        );

        $this->assertEquals($expectedConfig, $config);
        $this->assertAttributeEmpty('originalConfigs', $this->configManager);
    }

    /**
     * @dataProvider getConfigCacheProvider
     */
    public function testGetConfigCache(ConfigIdInterface $configId, $cachedConfig)
    {
        if ($configId instanceof FieldConfigId) {
            $this->configCache->expects($this->once())
                ->method('getFieldConfig')
                ->with($configId->getScope(), $configId->getClassName(), $configId->getFieldName())
                ->willReturn($cachedConfig);
        } else {
            $this->configCache->expects($this->once())
                ->method('getEntityConfig')
                ->with($configId->getScope(), $configId->getClassName())
                ->willReturn($cachedConfig);
        }

        $this->modelManager->expects($this->never())
            ->method('checkDatabase');
        $this->configCache->expects($this->never())
            ->method('getConfigurable');
        $this->modelManager->expects($this->never())
            ->method('getEntityModel');
        $this->modelManager->expects($this->never())
            ->method('getFieldModel');

        $result = $this->configManager->getConfig($configId);

        $this->assertSame($cachedConfig, $result);
        $this->assertArrayHasKey(
            $this->buildConfigKey($configId),
            $this->readAttribute($this->configManager, 'originalConfigs')
        );
    }

    public function getConfigCacheProvider()
    {
        return [
            [
                new EntityConfigId('entity', self::ENTITY_CLASS),
                $this->getConfig(new EntityConfigId('entity', self::ENTITY_CLASS))
            ],
            [
                new FieldConfigId('entity', self::ENTITY_CLASS, 'id', 'int'),
                $this->getConfig(new FieldConfigId('entity', self::ENTITY_CLASS, 'id', 'int'))
            ],
        ];
    }

    /**
     * @dataProvider getConfigNotCachedProvider
     */
    public function testGetConfigNotCached(ConfigIdInterface $configId, $getModelResult, $expectedConfig)
    {
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->willReturn(true);
        $this->configCache->expects($this->once())
            ->method('getConfigurable')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        if ($configId instanceof FieldConfigId) {
            $this->configCache->expects($this->once())
                ->method('getFieldConfig')
                ->with($configId->getScope(), $configId->getClassName(), $configId->getFieldName())
                ->willReturn(null);
        } else {
            $this->configCache->expects($this->once())
                ->method('getEntityConfig')
                ->with($configId->getScope(), $configId->getClassName())
                ->willReturn(null);
        }
        $this->configCache->expects($this->once())
            ->method('saveConfig')
            ->with($this->equalTo($expectedConfig));
        if ($configId instanceof FieldConfigId) {
            $this->modelManager->expects($this->never())
                ->method('getEntityModel');
            $this->modelManager->expects($this->once())
                ->method('getFieldModel')
                ->with($configId->getClassName(), $configId->getFieldName())
                ->willReturn($getModelResult);
        } else {
            $this->modelManager->expects($this->once())
                ->method('getEntityModel')
                ->with($configId->getClassName())
                ->willReturn($getModelResult);
            $this->modelManager->expects($this->never())
                ->method('getFieldModel');
        }

        $result = $this->configManager->getConfig($configId);

        $this->assertEquals($expectedConfig, $result);
        $this->assertArrayHasKey(
            $this->buildConfigKey($configId),
            $this->readAttribute($this->configManager, 'originalConfigs')
        );
    }

    public function getConfigNotCachedProvider()
    {
        return [
            [
                new EntityConfigId('entity', self::ENTITY_CLASS),
                $this->createEntityConfigModel(self::ENTITY_CLASS),
                $this->getConfig(new EntityConfigId('entity', self::ENTITY_CLASS))
            ],
            [
                new FieldConfigId('entity', self::ENTITY_CLASS, 'id', 'int'),
                $this->createFieldConfigModel(
                    $this->createEntityConfigModel(self::ENTITY_CLASS),
                    'id',
                    'int'
                ),
                $this->getConfig(new FieldConfigId('entity', self::ENTITY_CLASS, 'id', 'int'))
            ],
        ];
    }

    public function testGetIdsNoDatabase()
    {
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->willReturn(false);
        $result = $this->configManager->getIds('entity');
        $this->assertEquals([], $result);
    }

    /**
     * @dataProvider getIdsProvider
     */
    public function testGetIds($scope, $className, $withHidden, $expectedIds)
    {
        $models      = [
            $this->createEntityConfigModel('EntityClass1'),
            $this->createEntityConfigModel('EntityClass2'),
        ];
        $entityModel = $this->createEntityConfigModel('EntityClass1');
        $fieldModels = [
            $this->createFieldConfigModel($entityModel, 'f1', 'int'),
            $this->createFieldConfigModel($entityModel, 'f2', 'int'),
        ];

        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->willReturn(true);
        $this->modelManager->expects($this->once())
            ->method('getModels')
            ->with($className, $withHidden)
            ->willReturn($className ? $fieldModels : $models);

        $result = $this->configManager->getIds($scope, $className, $withHidden);
        $this->assertEquals($expectedIds, array_values($result));
    }

    public function getIdsProvider()
    {
        return [
            [
                'entity',
                null,
                true,
                [
                    new EntityConfigId('entity', 'EntityClass1'),
                    new EntityConfigId('entity', 'EntityClass2'),
                ]
            ],
            [
                'entity',
                null,
                false,
                [
                    new EntityConfigId('entity', 'EntityClass1'),
                    new EntityConfigId('entity', 'EntityClass2'),
                ]
            ],
            [
                'entity',
                'EntityClass1',
                true,
                [
                    new FieldConfigId('entity', 'EntityClass1', 'f1', 'int'),
                    new FieldConfigId('entity', 'EntityClass1', 'f2', 'int'),
                ]
            ],
            [
                'entity',
                'EntityClass1',
                false,
                [
                    new FieldConfigId('entity', 'EntityClass1', 'f1', 'int'),
                    new FieldConfigId('entity', 'EntityClass1', 'f2', 'int'),
                ]
            ],
        ];
    }

    /**
     * @dataProvider getConfigsProvider
     */
    public function testGetConfigs($scope, $className, $withHidden, $expectedConfigs)
    {
        $models      = [
            $this->createEntityConfigModel('EntityClass1'),
            $this->createEntityConfigModel('EntityClass2'),
        ];
        $entityModel = $this->createEntityConfigModel('EntityClass1');
        $fieldModels = [
            $this->createFieldConfigModel($entityModel, 'f1', 'int'),
            $this->createFieldConfigModel($entityModel, 'f2', 'int'),
        ];

        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->willReturn(true);
        $this->configCache->expects($this->any())
            ->method('getConfigurable')
            ->willReturn(true);
        $this->modelManager->expects($this->once())
            ->method('getModels')
            ->with($className, $withHidden)
            ->willReturn($className ? $fieldModels : $models);
        if ($className) {
            $this->modelManager->expects($this->any())
                ->method('getFieldModel')
                ->willReturnMap(
                    [
                        [$className, 'f1', $fieldModels[0]],
                        [$className, 'f2', $fieldModels[1]],
                    ]
                );
        } else {
            $this->modelManager->expects($this->any())
                ->method('getEntityModel')
                ->willReturnMap(
                    [
                        ['EntityClass1', $models[0]],
                        ['EntityClass2', $models[1]],
                    ]
                );
        }

        $result = $this->configManager->getConfigs($scope, $className, $withHidden);
        $this->assertEquals($expectedConfigs, array_values($result));
    }

    public function getConfigsProvider()
    {
        return [
            [
                'entity',
                null,
                true,
                [
                    $this->getConfig(new EntityConfigId('entity', 'EntityClass1')),
                    $this->getConfig(new EntityConfigId('entity', 'EntityClass2')),
                ]
            ],
            [
                'entity',
                null,
                false,
                [
                    $this->getConfig(new EntityConfigId('entity', 'EntityClass1')),
                    $this->getConfig(new EntityConfigId('entity', 'EntityClass2')),
                ]
            ],
            [
                'entity',
                'EntityClass1',
                true,
                [
                    $this->getConfig(new FieldConfigId('entity', 'EntityClass1', 'f1', 'int')),
                    $this->getConfig(new FieldConfigId('entity', 'EntityClass1', 'f2', 'int')),
                ]
            ],
            [
                'entity',
                'EntityClass1',
                false,
                [
                    $this->getConfig(new FieldConfigId('entity', 'EntityClass1', 'f1', 'int')),
                    $this->getConfig(new FieldConfigId('entity', 'EntityClass1', 'f2', 'int')),
                ]
            ],
        ];
    }

    public function testClearEntityCache()
    {
        $configId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $this->configCache->expects($this->once())
            ->method('deleteEntityConfig')
            ->with($configId->getClassName());
        $this->configManager->clearCache($configId);
    }

    public function testClearFieldCache()
    {
        $configId = new FieldConfigId('entity', self::ENTITY_CLASS, 'field');
        $this->configCache->expects($this->once())
            ->method('deleteFieldConfig')
            ->with($configId->getClassName(), $configId->getFieldName());
        $this->configManager->clearCache($configId);
    }

    public function testClearCacheAll()
    {
        $this->configCache->expects($this->once())
            ->method('deleteAllConfigs');
        $this->configManager->clearCache();
    }

    public function testClearConfigurableCache()
    {
        $this->configCache->expects($this->once())
            ->method('deleteAllConfigurable');
        $this->modelManager->expects($this->once())
            ->method('clearCheckDatabase');
        $this->configManager->clearConfigurableCache();
    }

    public function testHasConfigEntityModelWithNoModel()
    {
        $this->modelManager->expects($this->once())
            ->method('findEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        $result = $this->configManager->hasConfigEntityModel(self::ENTITY_CLASS);
        $this->assertFalse($result);
    }

    public function testHasConfigEntityModel()
    {
        $this->modelManager->expects($this->once())
            ->method('findEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->createEntityConfigModel(self::ENTITY_CLASS));

        $result = $this->configManager->hasConfigEntityModel(self::ENTITY_CLASS);
        $this->assertTrue($result);
    }

    public function testHasConfigFieldModelWithNoModel()
    {
        $this->modelManager->expects($this->once())
            ->method('findFieldModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->willReturn(null);

        $result = $this->configManager->hasConfigFieldModel(self::ENTITY_CLASS, 'id');
        $this->assertFalse($result);
    }

    public function testHasConfigFieldModel()
    {
        $this->modelManager->expects($this->once())
            ->method('findFieldModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->willReturn(
                $this->createFieldConfigModel(
                    $this->createEntityConfigModel(self::ENTITY_CLASS),
                    'id',
                    'int'
                )
            );

        $result = $this->configManager->hasConfigFieldModel(self::ENTITY_CLASS, 'id');
        $this->assertTrue($result);
    }

    public function testGetConfigEntityModel()
    {
        $model = $this->createEntityConfigModel(self::ENTITY_CLASS);

        $this->modelManager->expects($this->once())
            ->method('findEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($model);

        $result = $this->configManager->getConfigEntityModel(self::ENTITY_CLASS);
        $this->assertSame($model, $result);
    }

    public function testGetConfigFieldModel()
    {
        $model = $this->createFieldConfigModel(
            $this->createEntityConfigModel(self::ENTITY_CLASS),
            'id',
            'int'
        );

        $this->modelManager->expects($this->once())
            ->method('findFieldModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->willReturn($model);

        $result = $this->configManager->getConfigFieldModel(self::ENTITY_CLASS, 'id');
        $this->assertSame($model, $result);
    }

    /**
     * @dataProvider emptyNameProvider
     */
    public function testCreateConfigEntityModelForEmptyClassName($className)
    {
        $model = $this->createEntityConfigModel($className);

        $this->modelManager->expects($this->never())
            ->method('findEntityModel');
        $this->modelManager->expects($this->once())
            ->method('createEntityModel')
            ->with($className, ConfigModelManager::MODE_DEFAULT)
            ->willReturn($model);

        $result = $this->configManager->createConfigEntityModel($className);
        $this->assertSame($model, $result);
    }

    public function testCreateConfigEntityModelForExistingModel()
    {
        $model = $this->createEntityConfigModel(self::ENTITY_CLASS);

        $this->modelManager->expects($this->once())
            ->method('findEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($model);
        $this->modelManager->expects($this->never())
            ->method('createEntityModel');

        $result = $this->configManager->createConfigEntityModel(self::ENTITY_CLASS);
        $this->assertSame($model, $result);
    }

    public function testCreateConfigEntityModel()
    {
        $configId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $model    = $this->createEntityConfigModel(self::ENTITY_CLASS);
        $metadata = $this->getEntityMetadata(
            self::ENTITY_CLASS,
            ['translatable' => 'labelVal', 'other' => 'otherVal']
        );

        $this->modelManager->expects($this->once())
            ->method('findEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);
        $this->modelManager->expects($this->once())
            ->method('createEntityModel')
            ->with(self::ENTITY_CLASS, ConfigModelManager::MODE_DEFAULT)
            ->willReturn($model);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']);
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable', 'translatable10', 'auto_generated']);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::NEW_ENTITY_CONFIG,
                new EntityConfigEvent(self::ENTITY_CLASS, $this->configManager)
            );

        $config = $this->getConfig(
            $configId,
            [
                'other'          => 'otherVal',
                'translatable'   => 'labelVal',
                'other10'        => 'otherVal10',
                'translatable10' => 'labelVal10',
                'auto_generated' => 'oro.entityconfig.tests.unit.fixture.demoentity.entity_auto_generated'
            ]
        );

        $this->configCache->expects($this->once())
            ->method('saveConfig')
            ->with($config, true);

        $result = $this->configManager->createConfigEntityModel(self::ENTITY_CLASS);

        $this->assertEquals($model, $result);
        $this->assertEquals([$config], $this->configManager->getUpdateConfig());
    }

    public function testCreateConfigFieldModelForExistingModel()
    {
        $model = $this->createFieldConfigModel(
            $this->createEntityConfigModel(self::ENTITY_CLASS),
            'id',
            'int'
        );

        $this->modelManager->expects($this->once())
            ->method('findFieldModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->willReturn($model);
        $this->modelManager->expects($this->never())
            ->method('createFieldModel');

        $result = $this->configManager->createConfigFieldModel(self::ENTITY_CLASS, 'id', 'int');
        $this->assertSame($model, $result);
    }

    public function testCreateConfigFieldModel()
    {
        $configId        = new FieldConfigId('entity', self::ENTITY_CLASS, 'id', 'int');
        $model           = $this->createFieldConfigModel(
            $this->createEntityConfigModel(self::ENTITY_CLASS),
            'id',
            'int'
        );
        $metadata        = $this->getEntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = $this->getFieldMetadata(self::ENTITY_CLASS, 'id');
        $metadata->addPropertyMetadata($idFieldMetadata);

        $this->modelManager->expects($this->once())
            ->method('findFieldModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->willReturn(null);
        $this->modelManager->expects($this->once())
            ->method('createFieldModel')
            ->with(self::ENTITY_CLASS, 'id', 'int', ConfigModelManager::MODE_DEFAULT)
            ->willReturn($model);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $idFieldMetadata->defaultValues['entity'] = ['translatable' => 'labelVal', 'other' => 'otherVal'];
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_FIELD, 'int')
            ->willReturn(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']);
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(['translatable', 'translatable10', 'auto_generated']);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::NEW_FIELD_CONFIG,
                new FieldConfigEvent(self::ENTITY_CLASS, 'id', $this->configManager)
            );

        $config = $this->getConfig(
            $configId,
            [
                'other10'        => 'otherVal10',
                'translatable10' => 'labelVal10',
                'other'          => 'otherVal',
                'translatable'   => 'labelVal',
                'auto_generated' => 'oro.entityconfig.tests.unit.fixture.demoentity.id.auto_generated'
            ]
        );

        $this->configCache->expects($this->once())
            ->method('saveConfig')
            ->with($config, true);

        $result = $this->configManager->createConfigFieldModel(self::ENTITY_CLASS, 'id', 'int');

        $this->assertEquals($model, $result);
        $this->assertEquals(
            [$config],
            $this->configManager->getUpdateConfig()
        );
    }

    public function testUpdateConfigEntityModelWithNoForce()
    {
        $configId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $metadata = $this->getEntityMetadata(
            self::ENTITY_CLASS,
            [
                'translatable1' => 'labelVal1',
                'other1'        => 'otherVal1',
                'translatable2' => 'labelVal2',
                'other2'        => 'otherVal2',
            ]
        );

        $this->modelManager->expects($this->once())
            ->method('findEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->createEntityConfigModel(self::ENTITY_CLASS));
        $this->metadataFactory->expects($this->any())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']);
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable1', 'translatable2', 'translatable10', 'auto_generated']);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
        $config = $this->getConfig(
            $configId,
            [
                'translatable2' => 'labelVal2_old',
                'other2'        => 'otherVal2_old'
            ]
        );
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($config);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::UPDATE_ENTITY_CONFIG);

        $expectedConfig = $this->getConfig(
            $configId,
            [
                'translatable2'  => 'labelVal2_old',
                'other2'         => 'otherVal2_old',
                'translatable10' => 'labelVal10',
                'other10'        => 'otherVal10',
                'translatable1'  => 'labelVal1',
                'other1'         => 'otherVal1',
                'auto_generated' => 'oro.entityconfig.tests.unit.fixture.demoentity.entity_auto_generated'
            ]
        );

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->willReturnCallback(
                function ($c) use (&$actualConfig) {
                    $actualConfig = $c;
                }
            );

        $this->configManager->updateConfigEntityModel(self::ENTITY_CLASS);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testUpdateConfigEntityModelWithForce()
    {
        $configId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $metadata = $this->getEntityMetadata(
            self::ENTITY_CLASS,
            [
                'translatable1' => 'labelVal1',
                'other1'        => 'otherVal1',
                'translatable2' => 'labelVal2',
                'other2'        => 'otherVal2',
            ]
        );

        $this->modelManager->expects($this->once())
            ->method('findEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->createEntityConfigModel(self::ENTITY_CLASS));
        $this->metadataFactory->expects($this->any())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']);
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable1', 'translatable2', 'translatable10', 'auto_generated']);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
        $config = $this->getConfig(
            $configId,
            [
                'translatable2' => 'labelVal2_old',
                'other2'        => 'otherVal2_old'
            ]
        );
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($config);

        $expectedConfig = $this->getConfig(
            $configId,
            [
                'translatable2'  => 'labelVal2',
                'other2'         => 'otherVal2',
                'translatable10' => 'labelVal10',
                'other10'        => 'otherVal10',
                'translatable1'  => 'labelVal1',
                'other1'         => 'otherVal1',
                'auto_generated' => 'oro.entityconfig.tests.unit.fixture.demoentity.entity_auto_generated'
            ]
        );

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->willReturnCallback(
                function ($c) use (&$actualConfig) {
                    $actualConfig = $c;
                }
            );

        $this->configManager->updateConfigEntityModel(self::ENTITY_CLASS, true);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateConfigEntityModelWithForceForCustomEntity()
    {
        $configId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $metadata = $this->getEntityMetadata(
            self::ENTITY_CLASS,
            [
                'translatable1' => 'labelVal1',
                'other1'        => 'otherVal1',
                'translatable2' => 'labelVal2',
                'other2'        => 'otherVal2',
            ]
        );

        $this->modelManager->expects($this->once())
            ->method('findEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->createEntityConfigModel(self::ENTITY_CLASS));
        $this->metadataFactory->expects($this->any())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']);
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['translatable1', 'translatable2', 'translatable10', 'auto_generated']);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
        $config = $this->getConfig(
            $configId,
            [
                'translatable2' => 'labelVal2_old',
                'other2'        => 'otherVal2_old'
            ]
        );
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($config);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::UPDATE_ENTITY_CONFIG);
        $extendConfig = $this->getConfig(new EntityConfigId('extend', self::ENTITY_CLASS));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->any())
            ->method('getScope')
            ->willReturn('extend');
        $this->configProviderBag->addProvider($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($extendConfig);
        $extendPropertyConfigContainer = $this->getPropertyConfigContainerMock();
        $extendPropertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn(['owner' => ExtendScope::OWNER_SYSTEM]);
        $extendPropertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->willReturn([]);
        $extendConfigProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($extendPropertyConfigContainer);
        $extendConfigProvider->expects($this->never())
            ->method('persist');
        $expectedConfig = $this->getConfig(
            $configId,
            [
                'translatable2'  => 'labelVal2_old',
                'other2'         => 'otherVal2_old',
                'translatable10' => 'labelVal10',
                'other10'        => 'otherVal10',
                'translatable1'  => 'labelVal1',
                'other1'         => 'otherVal1',
                'auto_generated' => 'oro.entityconfig.tests.unit.fixture.demoentity.entity_auto_generated'
            ]
        );
        $actualConfig   = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->willReturnCallback(
                function ($c) use (&$actualConfig) {
                    $actualConfig = $c;
                }
            );

        $this->configManager->updateConfigEntityModel(self::ENTITY_CLASS, true);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testUpdateConfigFieldModelWithNoForce()
    {
        $configId        = new FieldConfigId('entity', self::ENTITY_CLASS, 'id', 'int');
        $metadata        = $this->getEntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = $this->getFieldMetadata(
            self::ENTITY_CLASS,
            'id',
            [
                'translatable1' => 'labelVal1',
                'other1'        => 'otherVal1',
                'translatable2' => 'labelVal2',
                'other2'        => 'otherVal2',
            ]
        );
        $metadata->addPropertyMetadata($idFieldMetadata);

        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_FIELD, 'int')
            ->willReturn(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']);
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(['translatable1', 'translatable2', 'translatable10', 'auto_generated']);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
        $config = $this->getConfig(
            $configId,
            [
                'translatable2' => 'labelVal2_old',
                'other2'        => 'otherVal2_old'
            ]
        );
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($config);

        $expectedConfig = $this->getConfig(
            $configId,
            [
                'translatable2'  => 'labelVal2_old',
                'other2'         => 'otherVal2_old',
                'translatable10' => 'labelVal10',
                'other10'        => 'otherVal10',
                'translatable1'  => 'labelVal1',
                'other1'         => 'otherVal1',
                'auto_generated' => 'oro.entityconfig.tests.unit.fixture.demoentity.id.auto_generated'
            ]
        );

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->willReturnCallback(
                function ($c) use (&$actualConfig) {
                    $actualConfig = $c;
                }
            );

        $this->configManager->updateConfigFieldModel(self::ENTITY_CLASS, 'id');
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testUpdateConfigFieldModelWithForce()
    {
        $configId        = new FieldConfigId('entity', self::ENTITY_CLASS, 'id', 'int');
        $metadata        = $this->getEntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = $this->getFieldMetadata(
            self::ENTITY_CLASS,
            'id',
            [
                'translatable1' => 'labelVal1',
                'other1'        => 'otherVal1',
                'translatable2' => 'labelVal2',
                'other2'        => 'otherVal2',
            ]
        );
        $metadata->addPropertyMetadata($idFieldMetadata);

        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_FIELD, 'int')
            ->willReturn(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']);
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(['translatable1', 'translatable2', 'translatable10', 'auto_generated']);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
        $config = $this->getConfig(
            $configId,
            [
                'translatable2' => 'labelVal2_old',
                'other2'        => 'otherVal2_old'
            ]
        );
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($config);

        $expectedConfig = $this->getConfig(
            $configId,
            [
                'translatable2'  => 'labelVal2',
                'other2'         => 'otherVal2',
                'translatable10' => 'labelVal10',
                'other10'        => 'otherVal10',
                'translatable1'  => 'labelVal1',
                'other1'         => 'otherVal1',
                'auto_generated' => 'oro.entityconfig.tests.unit.fixture.demoentity.id.auto_generated'
            ]
        );

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->willReturnCallback(
                function ($c) use (&$actualConfig) {
                    $actualConfig = $c;
                }
            );

        $this->configManager->updateConfigFieldModel(self::ENTITY_CLASS, 'id', true);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testUpdateConfigFieldModelWithForceForCustomField()
    {
        $configId        = new FieldConfigId('entity', self::ENTITY_CLASS, 'id', 'int');
        $metadata        = $this->getEntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = $this->getFieldMetadata(
            self::ENTITY_CLASS,
            'id',
            [
                'translatable1' => 'labelVal1',
                'other1'        => 'otherVal1',
                'translatable2' => 'labelVal2',
                'other2'        => 'otherVal2',
            ]
        );
        $metadata->addPropertyMetadata($idFieldMetadata);

        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($metadata);
        $propertyConfigContainer = $this->getPropertyConfigContainerMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_FIELD, 'int')
            ->willReturn(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']);
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(['translatable1', 'translatable2', 'translatable10', 'auto_generated']);
        $this->configProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);
        $config = $this->getConfig(
            $configId,
            [
                'translatable2' => 'labelVal2_old',
                'other2'        => 'otherVal2_old'
            ]
        );
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($config);

        $extendConfig = $this->getConfig(new FieldConfigId('extend', self::ENTITY_CLASS, 'id'));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfigProvider = $this->getConfigProviderMock();
        $extendConfigProvider->expects($this->any())
            ->method('getScope')
            ->willReturn('extend');
        $this->configProviderBag->addProvider($extendConfigProvider);
        $extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS, 'id')
            ->willReturn(true);
        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, 'id')
            ->willReturn($extendConfig);
        $extendPropertyConfigContainer = $this->getPropertyConfigContainerMock();
        $extendPropertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn(['owner' => ExtendScope::OWNER_SYSTEM]);
        $extendPropertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->willReturn([]);
        $extendConfigProvider->expects($this->any())
            ->method('getPropertyConfig')
            ->willReturn($extendPropertyConfigContainer);
        $extendConfigProvider->expects($this->never())
            ->method('persist');

        $expectedConfig = $this->getConfig(
            $configId,
            [
                'translatable2'  => 'labelVal2_old',
                'other2'         => 'otherVal2_old',
                'translatable10' => 'labelVal10',
                'other10'        => 'otherVal10',
                'translatable1'  => 'labelVal1',
                'other1'         => 'otherVal1',
                'auto_generated' => 'oro.entityconfig.tests.unit.fixture.demoentity.id.auto_generated'
            ]
        );

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->willReturnCallback(
                function ($c) use (&$actualConfig) {
                    $actualConfig = $c;
                }
            );

        $this->configManager->updateConfigFieldModel(self::ENTITY_CLASS, 'id', true);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testPersistAndMerge()
    {
        $configId       = new EntityConfigId('entity', self::ENTITY_CLASS);
        $config1        = $this->getConfig($configId, ['val1' => '1', 'val2' => '1']);
        $config2        = $this->getConfig($configId, ['val2' => '2_new', 'val3' => '3']);
        $expectedConfig = $this->getConfig($configId, ['val1' => '1', 'val2' => '2_new', 'val3' => '3']);

        $this->configManager->persist($config1);
        $this->configManager->merge($config2);
        $toBePersistedConfigs = $this->configManager->getUpdateConfig();

        $this->assertEquals([$expectedConfig], $toBePersistedConfigs);
    }

    protected function createEntityConfigModel(
        $className,
        $mode = ConfigModelManager::MODE_DEFAULT
    ) {
        $result = new EntityConfigModel($className);
        $result->setMode($mode);

        return $result;
    }

    protected function createFieldConfigModel(
        EntityConfigModel $entityConfigModel,
        $fieldName,
        $fieldType,
        $mode = ConfigModelManager::MODE_DEFAULT
    ) {
        $result = new FieldConfigModel($fieldName, $fieldType);
        $result->setEntity($entityConfigModel);
        $result->setMode($mode);

        return $result;
    }

    /**
     * @param string     $className
     * @param array|null $defaultValues
     *
     * @return EntityMetadata
     */
    protected function getEntityMetadata($className, $defaultValues = null)
    {
        $metadata       = new EntityMetadata($className);
        $metadata->mode = ConfigModelManager::MODE_DEFAULT;
        if (null !== $defaultValues) {
            $metadata->defaultValues['entity'] = $defaultValues;
        }

        return $metadata;
    }

    /**
     * @param string     $className
     * @param string     $fieldName
     * @param array|null $defaultValues
     *
     * @return FieldMetadata
     */
    protected function getFieldMetadata($className, $fieldName, $defaultValues = null)
    {
        $metadata = new FieldMetadata($className, $fieldName);
        if (null !== $defaultValues) {
            $metadata->defaultValues['entity'] = $defaultValues;
        }

        return $metadata;
    }

    /**
     * @param  ConfigIdInterface $configId
     * @param  array|null        $values
     *
     * @return Config
     */
    protected function getConfig(ConfigIdInterface $configId, array $values = null)
    {
        $config = new Config($configId);
        if (null !== $values) {
            $config->setValues($values);
        }

        return $config;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPropertyConfigContainerMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function emptyNameProvider()
    {
        return [
            [null],
            [''],
        ];
    }

    /**
     * @param ConfigIdInterface $configId
     * @return string
     */
    protected function buildConfigKey(ConfigIdInterface $configId)
    {
        return $configId instanceof FieldConfigId
            ? $configId->getScope() . '.' . $configId->getClassName() . '.' . $configId->getFieldName()
            : $configId->getScope() . '.' . $configId->getClassName();
    }
}
