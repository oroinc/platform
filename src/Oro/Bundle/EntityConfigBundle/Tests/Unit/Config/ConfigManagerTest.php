<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModelValue;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\NewEntityConfigModelEvent;
use Oro\Bundle\EntityConfigBundle\Event\NewFieldConfigModelEvent;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
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

    public function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider->expects($this->any())
            ->method('getScope')
            ->will($this->returnValue('test'));

        $this->configProviderBag = new ConfigProviderBag();
        $this->configProviderBag->addProvider($this->configProvider);
        $this->container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('has')
            ->will(
                $this->returnCallback(
                    function ($id) {
                        switch ($id) {
                            case 'ConfigProviderBag':
                                return true;
                            default:
                                return false;
                        }
                    }
                )
            );
        $configProviderBag = $this->configProviderBag;
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($id) use (&$configProviderBag) {
                        switch ($id) {
                            case 'ConfigProviderBag':
                                return $configProviderBag;
                            default:
                                return null;
                        }
                    }
                )
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
            $this->auditManager
        );

        $this->configManager->setCache($this->configCache);
    }

    public function testGetProviderBag()
    {
        $this->assertTrue($this->configManager->getProviderBag() === $this->configProviderBag);
    }

    public function testGetProviders()
    {
        $providers = $this->configManager->getProviders();
        $this->assertCount(1, $providers);
        $this->assertSame($this->configProvider, $providers['test']);
    }

    public function testGetProvider()
    {
        $this->assertSame($this->configProvider, $this->configManager->getProvider('test'));
    }

    public function testGetEventDispatcher()
    {
        $this->assertSame($this->eventDispatcher, $this->configManager->getEventDispatcher());
    }

    public function testGetEntityMetadata()
    {
        $this->assertNull($this->configManager->getEntityMetadata('SomeUndefinedClass'));

        $metadata = new EntityMetadata(self::ENTITY_CLASS);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));
        $this->assertSame($metadata, $this->configManager->getEntityMetadata(self::ENTITY_CLASS));
    }

    public function testGetFieldMetadata()
    {
        $this->assertNull($this->configManager->getFieldMetadata('SomeUndefinedClass', 'test'));

        $metadata        = new EntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = new FieldMetadata(self::ENTITY_CLASS, 'id');
        $metadata->addPropertyMetadata($idFieldMetadata);
        $this->metadataFactory->expects($this->exactly(2))
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));

        $this->assertNull(
            $this->configManager->getFieldMetadata(self::ENTITY_CLASS, 'undefinedField')
        );
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
            ->will($this->returnValue($checkDatabaseResult));
        if ($checkDatabaseResult) {
            $this->configCache->expects($this->once())
                ->method('getConfigurable')
                ->with($className, $fieldName)
                ->will($this->returnValue($cachedResult));
            if (null === $cachedResult) {
                $this->configCache->expects($this->once())
                    ->method('setConfigurable')
                    ->with($expectedResult, $className, $fieldName);
                $this->modelManager->expects($this->once())
                    ->method('findModel')
                    ->with($className, $fieldName)
                    ->will($this->returnValue($findModelResult));
            }
        }

        $result = $this->configManager->hasConfig($className, $fieldName);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\LogicException
     */
    public function testGetConfigNoDatabase()
    {
        $configId = new EntityConfigId(self::ENTITY_CLASS, 'test');
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->will($this->returnValue(false));
        $this->configManager->getConfig($configId);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\RuntimeException
     */
    public function testGetConfigForNotConfigurable()
    {
        $configId = new EntityConfigId(self::ENTITY_CLASS, 'test');
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->will($this->returnValue(true));
        $this->configCache->expects($this->once())
            ->method('getConfigurable')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue(false));
        $this->configManager->getConfig($configId);
    }

    /**
     * @dataProvider getConfigCacheProvider
     */
    public function testGetConfigCache($configId, $cachedConfig)
    {
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->will($this->returnValue(true));
        $this->configCache->expects($this->once())
            ->method('getConfigurable')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue(true));
        $this->configCache->expects($this->once())
            ->method('loadConfigFromCache')
            ->with($this->identicalTo($configId))
            ->will($this->returnValue($cachedConfig));
        $this->modelManager->expects($this->never())
            ->method('getModelByConfigId');

        $result = $this->configManager->getConfig($configId);
        $this->assertSame($cachedConfig, $result);

        // test local cache
        $result = $this->configManager->getConfig($configId);
        $this->assertSame($cachedConfig, $result);
    }

    /**
     * @dataProvider getConfigNotCachedProvider
     */
    public function testGetConfigNotCached($configId, $getModelResult, $expectedConfig)
    {
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->will($this->returnValue(true));
        $this->configCache->expects($this->once())
            ->method('getConfigurable')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue(true));
        $this->configCache->expects($this->once())
            ->method('loadConfigFromCache')
            ->with($this->identicalTo($configId))
            ->will($this->returnValue(null));
        $this->configCache->expects($this->once())
            ->method('putConfigInCache')
            ->with($this->equalTo($expectedConfig));
        $this->modelManager->expects($this->once())
            ->method('getModelByConfigId')
            ->with($this->identicalTo($configId))
            ->will($this->returnValue($getModelResult));

        $result = $this->configManager->getConfig($configId);
        $this->assertEquals($expectedConfig, $result);
    }

    public function testGetIdsNoDatabase()
    {
        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->will($this->returnValue(false));
        $result = $this->configManager->getIds('test');
        $this->assertEquals([], $result);
    }

    /**
     * @dataProvider getIdsProvider
     */
    public function testGetIds($scope, $className, $withHidden, $expectedIds)
    {
        $models      = [
            $this->createEntityConfigModel('EntityClass1', 'test'),
            $this->createEntityConfigModel('EntityClass2', 'test', ConfigModelManager::MODE_HIDDEN),
            $this->createEntityConfigModel('EntityClass3', 'test1'),
        ];
        $entityModel = $this->createEntityConfigModel('EntityClass1', 'test');
        $fieldModels = [
            $this->createFieldConfigModel($entityModel, 'test', 'f1'),
            $this->createFieldConfigModel($entityModel, 'test', 'f2', null, ConfigModelManager::MODE_HIDDEN),
        ];

        $this->modelManager->expects($this->any())
            ->method('checkDatabase')
            ->will($this->returnValue(true));
        $this->modelManager->expects($this->once())
            ->method('getModels')
            ->with($className)
            ->will($this->returnValue($className ? $fieldModels : $models));

        $result = $this->configManager->getIds($scope, $className, $withHidden);
        $this->assertEquals($expectedIds, array_values($result));
    }

    public function testClearCache()
    {
        $configId = new EntityConfigId(self::ENTITY_CLASS, 'test');
        $this->configCache->expects($this->once())
            ->method('removeConfigFromCache')
            ->with($this->equalTo($configId));
        $this->configManager->clearCache($configId);
    }

    public function testClearCacheAll()
    {
        $this->configCache->expects($this->once())
            ->method('removeAll');
        $this->configManager->clearCacheAll();
    }

    public function testClearConfigurableCache()
    {
        $this->configCache->expects($this->once())
            ->method('removeAllConfigurable');
        $this->modelManager->expects($this->once())
            ->method('clearCheckDatabase');
        $this->configManager->clearConfigurableCache();
    }

    public function testHasConfigEntityModelWithNoModel()
    {
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue(null));
        $result = $this->configManager->hasConfigEntityModel(self::ENTITY_CLASS);
        $this->assertFalse($result);
    }

    public function testHasConfigEntityModel()
    {
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($this->createEntityConfigModel(self::ENTITY_CLASS, 'test')));
        $result = $this->configManager->hasConfigEntityModel(self::ENTITY_CLASS);
        $this->assertTrue($result);
    }

    public function testHasConfigFieldModelWithNoModel()
    {
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->will($this->returnValue(null));
        $result = $this->configManager->hasConfigFieldModel(self::ENTITY_CLASS, 'id');
        $this->assertFalse($result);
    }

    public function testHasConfigFieldModel()
    {
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->will(
                $this->returnValue(
                    $this->createFieldConfigModel(
                        $this->createEntityConfigModel(self::ENTITY_CLASS, 'test'),
                        'test',
                        'id'
                    )
                )
            );
        $result = $this->configManager->hasConfigFieldModel(self::ENTITY_CLASS, 'id');
        $this->assertTrue($result);
    }

    public function testGetConfigEntityModel()
    {
        $model = $this->createEntityConfigModel(self::ENTITY_CLASS, 'test');
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($model));
        $result = $this->configManager->getConfigEntityModel(self::ENTITY_CLASS);
        $this->assertSame($model, $result);
    }

    public function testGetConfigFieldModel()
    {
        $model = $this->createFieldConfigModel(
            $this->createEntityConfigModel(self::ENTITY_CLASS, 'test'),
            'test',
            'id'
        );
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->will($this->returnValue($model));
        $result = $this->configManager->getConfigFieldModel(self::ENTITY_CLASS, 'id');
        $this->assertSame($model, $result);
    }

    public function testCreateConfigEntityModelForExistingModel()
    {
        $model = $this->createEntityConfigModel(self::ENTITY_CLASS, 'test');
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($model));
        $this->modelManager->expects($this->never())
            ->method('createEntityModel');
        $result = $this->configManager->createConfigEntityModel(self::ENTITY_CLASS);
        $this->assertSame($model, $result);
    }

    public function testCreateConfigEntityModel()
    {
        $model = $this->createEntityConfigModel(self::ENTITY_CLASS, 'test');
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue(null));
        $this->modelManager->expects($this->once())
            ->method('createEntityModel')
            ->with(self::ENTITY_CLASS, ConfigModelManager::MODE_DEFAULT)
            ->will($this->returnValue($model));
        $metadata                        = new EntityMetadata(self::ENTITY_CLASS);
        $metadata->defaultValues['test'] = ['translatable' => 'labelVal', 'other' => 'otherVal'];
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));
        $propertyConfigContainer =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer')
                ->disableOriginalConstructor()
                ->getMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->will($this->returnValue(['translatable']));
        $this->configProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($propertyConfigContainer));
        $configId = new EntityConfigId(self::ENTITY_CLASS, 'test');
        $config   = new Config($configId);
        $this->configProvider->expects($this->once())
            ->method('createConfig')
            ->with(
                $configId,
                [
                    'translatable' => 'oro.configtests.unit.fixture.demoentity.entity_translatable',
                    'other'        => 'otherVal'
                ]
            )
            ->will($this->returnValue($config));
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::NEW_ENTITY_CONFIG_MODEL,
                new NewEntityConfigModelEvent($model, $this->configManager)
            );

        $result = $this->configManager->createConfigEntityModel(self::ENTITY_CLASS);
        $this->assertSame($model, $result);

        // test that a config for a created model is stored in a local cache
        $result = $this->configManager->getConfig($configId);
        $this->assertSame($config, $result);
    }

    public function testCreateConfigFieldModelForExistingModel()
    {
        $model = $this->createFieldConfigModel(
            $this->createEntityConfigModel(self::ENTITY_CLASS, 'test'),
            'test',
            'id'
        );
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->will($this->returnValue($model));
        $this->modelManager->expects($this->never())
            ->method('createFieldModel');
        $result = $this->configManager->createConfigFieldModel(self::ENTITY_CLASS, 'id', 'int');
        $this->assertSame($model, $result);
    }

    public function testCreateConfigFieldModel()
    {
        $model = $this->createFieldConfigModel(
            $this->createEntityConfigModel(self::ENTITY_CLASS, 'test'),
            'test',
            'id'
        );
        $this->modelManager->expects($this->once())
            ->method('findModel')
            ->with(self::ENTITY_CLASS, 'id')
            ->will($this->returnValue(null));
        $this->modelManager->expects($this->once())
            ->method('createFieldModel')
            ->with(self::ENTITY_CLASS, 'id', 'int', ConfigModelManager::MODE_DEFAULT)
            ->will($this->returnValue($model));
        $metadata        = new EntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = new FieldMetadata(self::ENTITY_CLASS, 'id');
        $metadata->addPropertyMetadata($idFieldMetadata);
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));
        $idFieldMetadata->defaultValues['test'] = ['translatable' => 'labelVal', 'other' => 'otherVal'];
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));
        $propertyConfigContainer =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer')
                ->disableOriginalConstructor()
                ->getMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_FIELD)
            ->will($this->returnValue(['translatable']));
        $this->configProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($propertyConfigContainer));
        $configId = new FieldConfigId(self::ENTITY_CLASS, 'test', 'id', 'int');
        $config   = new Config($configId);
        $this->configProvider->expects($this->once())
            ->method('createConfig')
            ->with(
                $configId,
                [
                    'translatable' => 'oro.configtests.unit.fixture.demoentity.id.translatable',
                    'other'        => 'otherVal'
                ]
            )
            ->will($this->returnValue($config));
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                Events::NEW_FIELD_CONFIG_MODEL,
                new NewFieldConfigModelEvent($model, $this->configManager)
            );

        $result = $this->configManager->createConfigFieldModel(self::ENTITY_CLASS, 'id', 'int');
        $this->assertSame($model, $result);

        // test that a config for a created model is stored in a local cache
        $result = $this->configManager->getConfig($configId);
        $this->assertSame($config, $result);
    }

    public function testUpdateConfigEntityModelWithNoForce()
    {
        $metadata                        = new EntityMetadata(self::ENTITY_CLASS);
        $metadata->defaultValues['test'] = [
            'translatable1' => 'labelVal1',
            'other1'        => 'otherVal1',
            'translatable2' => 'labelVal2',
            'other2'        => 'otherVal2',
        ];
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));
        $propertyConfigContainer =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer')
                ->disableOriginalConstructor()
                ->getMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->will($this->returnValue(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']));
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->will($this->returnValue(['translatable1', 'translatable2', 'translatable10']));
        $this->configProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($propertyConfigContainer));
        $configId = new EntityConfigId(self::ENTITY_CLASS, 'test');
        $config   = new Config($configId);
        $config->set('translatable2', 'labelVal2_old');
        $config->set('other2', 'otherVal2_old');
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($config));

        // TODO: here is an error ('translatable2' and 'other2' must not be updated) - will be fixed soon
        $expectedConfig = new Config($configId);
        $expectedConfig->set('translatable1', 'oro.configtests.unit.fixture.demoentity.entity_translatable1');
        $expectedConfig->set('other1', 'otherVal1');
        $expectedConfig->set('translatable2', 'oro.configtests.unit.fixture.demoentity.entity_translatable2');
        $expectedConfig->set('other2', 'otherVal2');
        $expectedConfig->set('translatable10', 'oro.configtests.unit.fixture.demoentity.entity_translatable10');
        $expectedConfig->set('other10', 'otherVal10');

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->will(
                $this->returnCallback(
                    function ($c) use (&$actualConfig) {
                        $actualConfig = $c;
                    }
                )
            );

        $this->configManager->updateConfigEntityModel(self::ENTITY_CLASS);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testUpdateConfigEntityModelWithForce()
    {
        $metadata                        = new EntityMetadata(self::ENTITY_CLASS);
        $metadata->defaultValues['test'] = [
            'translatable1' => 'labelVal1',
            'other1'        => 'otherVal1',
            'translatable2' => 'labelVal2',
            'other2'        => 'otherVal2',
        ];
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));
        $propertyConfigContainer =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer')
                ->disableOriginalConstructor()
                ->getMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->will($this->returnValue(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']));
        $propertyConfigContainer->expects($this->once())
            ->method('getTranslatableValues')
            ->with(PropertyConfigContainer::TYPE_ENTITY)
            ->will($this->returnValue(['translatable1', 'translatable2', 'translatable10']));
        $this->configProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($propertyConfigContainer));
        $configId = new EntityConfigId(self::ENTITY_CLASS, 'test');
        $config   = new Config($configId);
        $config->set('translatable2', 'labelVal2_old');
        $config->set('other2', 'otherVal2_old');
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($config));

        $expectedConfig = new Config($configId);
        $expectedConfig->set('translatable1', 'oro.configtests.unit.fixture.demoentity.entity_translatable1');
        $expectedConfig->set('other1', 'otherVal1');
        $expectedConfig->set('translatable2', 'oro.configtests.unit.fixture.demoentity.entity_translatable2');
        $expectedConfig->set('other2', 'otherVal2');
        $expectedConfig->set('translatable10', 'oro.configtests.unit.fixture.demoentity.entity_translatable10');
        $expectedConfig->set('other10', 'otherVal10');

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->will(
                $this->returnCallback(
                    function ($c) use (&$actualConfig) {
                        $actualConfig = $c;
                    }
                )
            );

        $this->configManager->updateConfigEntityModel(self::ENTITY_CLASS, true);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testUpdateConfigFieldModelWithNoForce()
    {
        $metadata        = new EntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = new FieldMetadata(self::ENTITY_CLASS, 'id');
        $metadata->addPropertyMetadata($idFieldMetadata);
        $idFieldMetadata->defaultValues['test'] = [
            'translatable1' => 'labelVal1',
            'other1'        => 'otherVal1',
            'translatable2' => 'labelVal2',
            'other2'        => 'otherVal2',
        ];
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));
        $propertyConfigContainer =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer')
                ->disableOriginalConstructor()
                ->getMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->will($this->returnValue(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']));
        // TODO: processing for translatable values is not implemented
        //$propertyConfigContainer->expects($this->once())
        //    ->method('getTranslatableValues')
        //    ->with(PropertyConfigContainer::TYPE_ENTITY)
        //    ->will($this->returnValue(['translatable1', 'translatable2', 'translatable10']));
        $this->configProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($propertyConfigContainer));
        $configId = new FieldConfigId(self::ENTITY_CLASS, 'test', 'id', 'int');
        $config   = new Config($configId);
        $config->set('translatable2', 'labelVal2_old');
        $config->set('other2', 'otherVal2_old');
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($config));

        // TODO: here is an error ('translatable2' and 'other2' must not be updated) - will be fixed soon
        // TODO: processing for translatable values is not implemented
        $expectedConfig = new Config($configId);
        $expectedConfig->set('translatable1', 'labelVal1');
        $expectedConfig->set('other1', 'otherVal1');
        $expectedConfig->set('translatable2', 'labelVal2');
        $expectedConfig->set('other2', 'otherVal2');
        $expectedConfig->set('translatable10', 'labelVal10');
        $expectedConfig->set('other10', 'otherVal10');

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->will(
                $this->returnCallback(
                    function ($c) use (&$actualConfig) {
                        $actualConfig = $c;
                    }
                )
            );

        $this->configManager->updateConfigFieldModel(self::ENTITY_CLASS, 'id');
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testUpdateConfigFieldModelWithForce()
    {
        $metadata        = new EntityMetadata(self::ENTITY_CLASS);
        $idFieldMetadata = new FieldMetadata(self::ENTITY_CLASS, 'id');
        $metadata->addPropertyMetadata($idFieldMetadata);
        $idFieldMetadata->defaultValues['test'] = [
            'translatable1' => 'labelVal1',
            'other1'        => 'otherVal1',
            'translatable2' => 'labelVal2',
            'other2'        => 'otherVal2',
        ];
        $this->metadataFactory->expects($this->once())
            ->method('getMetadataForClass')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($metadata));
        $propertyConfigContainer =
            $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer')
                ->disableOriginalConstructor()
                ->getMock();
        $propertyConfigContainer->expects($this->once())
            ->method('getDefaultValues')
            ->will($this->returnValue(['translatable10' => 'labelVal10', 'other10' => 'otherVal10']));
        // TODO: processing for translatable values is not implemented
        //$propertyConfigContainer->expects($this->once())
        //    ->method('getTranslatableValues')
        //    ->with(PropertyConfigContainer::TYPE_ENTITY)
        //    ->will($this->returnValue(['translatable1', 'translatable2', 'translatable10']));
        $this->configProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($propertyConfigContainer));
        $configId = new FieldConfigId(self::ENTITY_CLASS, 'test', 'id', 'int');
        $config   = new Config($configId);
        $config->set('translatable2', 'labelVal2_old');
        $config->set('other2', 'otherVal2_old');
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue($config));

        // TODO: here is an error ('translatable2' and 'other2' must not be updated) - will be fixed soon
        // TODO: processing for translatable values is not implemented
        $expectedConfig = new Config($configId);
        $expectedConfig->set('translatable1', 'labelVal1');
        $expectedConfig->set('other1', 'otherVal1');
        $expectedConfig->set('translatable2', 'labelVal2');
        $expectedConfig->set('other2', 'otherVal2');
        $expectedConfig->set('translatable10', 'labelVal10');
        $expectedConfig->set('other10', 'otherVal10');

        $actualConfig = null;
        $this->configProvider->expects($this->once())
            ->method('persist')
            ->will(
                $this->returnCallback(
                    function ($c) use (&$actualConfig) {
                        $actualConfig = $c;
                    }
                )
            );

        $this->configManager->updateConfigFieldModel(self::ENTITY_CLASS, 'id', true);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    public function testPersistAndMerge()
    {
        $configId = new EntityConfigId(self::ENTITY_CLASS, 'test');
        $config1   = new Config($configId);
        $config1->set('val1', '1');
        $config1->set('val2', '2');
        $config2   = new Config($configId);
        $config2->set('val2', '2_new');
        $config2->set('val3', '3');

        $expectedConfig   = new Config($configId);
        $expectedConfig->set('val1', '1');
        $expectedConfig->set('val2', '2_new');
        $expectedConfig->set('val3', '3');

        $this->configManager->persist($config1);
        $this->configManager->merge($config2);
        $toBePersistedConfigs = $this->configManager->getUpdateConfig();

        $this->assertEquals([$expectedConfig], $toBePersistedConfigs);
    }

    protected function createEntityConfigModel(
        $className,
        $scope,
        $mode = ConfigModelManager::MODE_DEFAULT,
        array $attributes = []
    ) {
        $result = new EntityConfigModel($className);
        $result->setMode($mode);
        $values = [];
        foreach ($attributes as $code => $val) {
            if (is_array($val)) {
                $value = new ConfigModelValue($code, $scope, $val);
            } else {
                $value = new ConfigModelValue(
                    $code,
                    $val['scope'],
                    $val['value'],
                    isset($val['serializable']) ? $val['serializable'] : false
                );
            }
            $values[] = $value;
        }
        $result->setValues($values);

        return $result;
    }

    protected function createFieldConfigModel(
        EntityConfigModel $entityConfigModel,
        $scope,
        $fieldName,
        $fieldType = null,
        $mode = ConfigModelManager::MODE_DEFAULT,
        array $attributes = []
    ) {
        $result = new FieldConfigModel($fieldName, $fieldType);
        $result->setEntity($entityConfigModel);
        $result->setMode($mode);
        $values = [];
        foreach ($attributes as $code => $val) {
            if (is_array($val)) {
                $value = new ConfigModelValue($code, $scope, $val);
            } else {
                $value = new ConfigModelValue(
                    $code,
                    $val['scope'],
                    $val['value'],
                    isset($val['serializable']) ? $val['serializable'] : false
                );
            }
            $values[] = $value;
        }
        $result->setValues($values);

        return $result;
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
                $this->createEntityConfigModel(self::ENTITY_CLASS, 'test'),
                self::ENTITY_CLASS,
                null
            ],
            'has model (field)'    => [
                true,
                true,
                null,
                $this->createFieldConfigModel($this->createEntityConfigModel(self::ENTITY_CLASS, 'test'), 'test', 'id'),
                self::ENTITY_CLASS,
                'id'
            ],
        ];
    }

    public function getConfigCacheProvider()
    {
        return [
            [
                new EntityConfigId(self::ENTITY_CLASS, 'test'),
                new Config(new EntityConfigId(self::ENTITY_CLASS, 'test'))
            ],
            [
                new FieldConfigId(self::ENTITY_CLASS, 'test', 'id'),
                new Config(new FieldConfigId(self::ENTITY_CLASS, 'test', 'id'))
            ],
        ];
    }

    public function getConfigNotCachedProvider()
    {
        return [
            [
                new EntityConfigId(self::ENTITY_CLASS, 'test'),
                $this->createEntityConfigModel(self::ENTITY_CLASS, 'test'),
                new Config(new EntityConfigId(self::ENTITY_CLASS, 'test'))
            ],
            [
                new FieldConfigId(self::ENTITY_CLASS, 'test', 'id'),
                $this->createFieldConfigModel($this->createEntityConfigModel(self::ENTITY_CLASS, 'test'), 'test', 'id'),
                new Config(new FieldConfigId(self::ENTITY_CLASS, 'test', 'id'))
            ],
        ];
    }

    public function getIdsProvider()
    {
        return [
            [
                'test',
                null,
                true,
                [
                    new EntityConfigId('EntityClass1', 'test'),
                    new EntityConfigId('EntityClass2', 'test'),
                    new EntityConfigId('EntityClass3', 'test'),
                ]
            ],
            [
                'test',
                null,
                false,
                [
                    new EntityConfigId('EntityClass1', 'test'),
                    new EntityConfigId('EntityClass3', 'test'),
                ]
            ],
            [
                'test',
                'EntityClass1',
                true,
                [
                    new FieldConfigId('EntityClass1', 'test', 'f1'),
                    new FieldConfigId('EntityClass1', 'test', 'f2'),
                ]
            ],
            [
                'test',
                'EntityClass1',
                false,
                [
                    new FieldConfigId('EntityClass1', 'test', 'f1'),
                ]
            ],
        ];
    }
}
