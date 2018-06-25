<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    const FIELD_NAME = 'someExtendFieldName';
    const ENTITY_CLASS_NAME = 'Oro\Bundle\SomeBundle\Entity\SomeEntity';

    const DEFAULT_EXTEND_OPTIONS = [
        'is_extend' => true,
        'origin' => ExtendScope::ORIGIN_CUSTOM,
        'owner' => ExtendScope::OWNER_CUSTOM,
        'state' => ExtendScope::STATE_NEW
    ];

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FieldConfigModel|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldConfigModel;

    /** @var ConfigHelper */
    private $configHelper;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldConfigModel = $this->createMock(FieldConfigModel::class);

        $this->configHelper = new ConfigHelper($this->configManager);
    }

    /**
     * @param string $entityClass
     * @param array  $values
     *
     * @return Config
     */
    protected function getEntityConfig($entityClass, array $values)
    {
        $config = new Config(new EntityConfigId('extend', $entityClass));
        $config->setValues($values);

        return $config;
    }

    public function testGetExtendRequireJsModules()
    {
        $modules = ['module1'];

        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $propertyConfig = $this->getMockBuilder(PropertyConfigContainer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $propertyConfig
            ->expects($this->once())
            ->method('getRequireJsModules')
            ->willReturn($modules);

        $configProvider
            ->expects($this->once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($configProvider);

        $this->assertEquals($modules, $this->configHelper->getExtendRequireJsModules());
    }

    public function testGetEntityConfigByField()
    {
        $scope = 'scope';
        $className = 'className';
        $fieldConfigModel = $this->createMock(FieldConfigModel::class);

        $entityConfigModel = $this->createMock(EntityConfigModel::class);

        $entityConfigModel
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $fieldConfigModel
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entityConfigModel);

        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityConfig = $this->createMock(ConfigInterface::class);

        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($entityConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($configProvider);

        $this->assertEquals($entityConfig, $this->configHelper->getEntityConfigByField($fieldConfigModel, $scope));
    }

    public function testGetFieldConfig()
    {
        $scope = 'scope';
        $className = 'className';
        $fieldName = 'fieldName';

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn($fieldName);

        $entityConfigModel = $this->createMock(EntityConfigModel::class);

        $entityConfigModel
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entityConfigModel);

        $fieldConfig = $this->createMock(ConfigInterface::class);

        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($fieldConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($configProvider);

        $this->assertEquals($fieldConfig, $this->configHelper->getFieldConfig($this->fieldConfigModel, $scope));
    }

    public function testFilterEntityConfigByField()
    {
        $scope = 'scope';
        $className = 'className';
        $filterResults = ['one', 'two'];
        $callback = function () {
        };

        $entityConfigModel = $this->createMock(EntityConfigModel::class);

        $entityConfigModel
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entityConfigModel);

        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->once())
            ->method('filter')
            ->with($callback, $className)
            ->willReturn($filterResults);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($configProvider);

        $this->assertEquals(
            $filterResults,
            $this->configHelper->filterEntityConfigByField($this->fieldConfigModel, $scope, $callback)
        );
    }

    public function testCreateFieldOptionsForSimpleFieldType()
    {
        $extendEntityConfig = $this->getEntityConfig('Test\Entity', []);
        $fieldType = 'string';
        $additionalFieldOptions = [];

        list($resultFieldType, $resultFieldOptions) = $this->configHelper->createFieldOptions(
            $extendEntityConfig,
            $fieldType,
            $additionalFieldOptions
        );
        $this->assertEquals($fieldType, $resultFieldType);
        $this->assertEquals(
            [
                'extend' => static::DEFAULT_EXTEND_OPTIONS
            ],
            $resultFieldOptions
        );
    }

    public function testCreateFieldOptionsForSimpleFieldTypeWithAdditionalFieldOptions()
    {
        $extendEntityConfig = $this->getEntityConfig('Test\Entity', []);
        $fieldType = 'string';
        $additionalFieldOptions = [
            'anotherScope' => [
                'option1' => 'value1'
            ]
        ];

        list($resultFieldType, $resultFieldOptions) = $this->configHelper->createFieldOptions(
            $extendEntityConfig,
            $fieldType,
            $additionalFieldOptions
        );
        $this->assertEquals($fieldType, $resultFieldType);
        $this->assertEquals(
            [
                'extend'       => static::DEFAULT_EXTEND_OPTIONS,
                'anotherScope' => [
                    'option1' => 'value1'
                ]
            ],
            $resultFieldOptions
        );
    }

    public function testCreateFieldOptionsForPublicEnumFieldType()
    {
        $extendEntityConfig = $this->getEntityConfig('Test\Entity', []);
        $fieldType = 'enum||some_enum_code';
        $additionalFieldOptions = [];

        list($resultFieldType, $resultFieldOptions) = $this->configHelper->createFieldOptions(
            $extendEntityConfig,
            $fieldType,
            $additionalFieldOptions
        );
        $this->assertEquals('enum', $resultFieldType);
        $this->assertEquals(
            [
                'extend' => static::DEFAULT_EXTEND_OPTIONS,
                'enum' => [
                    'enum_code' => 'some_enum_code'
                ]
            ],
            $resultFieldOptions
        );
    }

    public function testCreateFieldOptionsForPublicMultiEnumFieldType()
    {
        $extendEntityConfig = $this->getEntityConfig('Test\Entity', []);
        $fieldType = 'multiEnum||some_enum_code';
        $additionalFieldOptions = [];

        list($resultFieldType, $resultFieldOptions) = $this->configHelper->createFieldOptions(
            $extendEntityConfig,
            $fieldType,
            $additionalFieldOptions
        );
        $this->assertEquals('multiEnum', $resultFieldType);
        $this->assertEquals(
            [
                'extend' => static::DEFAULT_EXTEND_OPTIONS,
                'enum' => [
                    'enum_code' => 'some_enum_code'
                ]
            ],
            $resultFieldOptions
        );
    }

    public function testCreateFieldOptionsForOneToManyReverseRelationFieldType()
    {
        $extendEntityConfig = $this->getEntityConfig(
            'Test\Entity',
            [
                'relation' => [
                    'oneToMany|Test\Entity|Test\TargetEntity|owningSideField' => [
                        'target_entity' => 'Test\TargetEntity'
                    ]
                ]
            ]
        );
        $fieldType = 'oneToMany|Test\Entity|Test\TargetEntity|owningSideField||targetSideField';
        $additionalFieldOptions = [];

        list($resultFieldType, $resultFieldOptions) = $this->configHelper->createFieldOptions(
            $extendEntityConfig,
            $fieldType,
            $additionalFieldOptions
        );
        $this->assertEquals('manyToOne', $resultFieldType);
        $this->assertEquals(
            [
                'extend' => [
                    'is_extend' => true,
                    'origin'        => ExtendScope::ORIGIN_CUSTOM,
                    'owner'         => ExtendScope::OWNER_CUSTOM,
                    'state'         => ExtendScope::STATE_NEW,
                    'relation_key'  => 'oneToMany|Test\Entity|Test\TargetEntity|owningSideField',
                    'target_entity' => 'Test\TargetEntity',
                ]
            ],
            $resultFieldOptions
        );
    }

    public function testCreateFieldOptionsForManyToOneReverseRelationFieldType()
    {
        $extendEntityConfig = $this->getEntityConfig(
            'Test\Entity',
            [
                'relation' => [
                    'manyToOne|Test\Entity|Test\TargetEntity|owningSideField' => [
                        'target_entity' => 'Test\TargetEntity'
                    ]
                ]
            ]
        );
        $fieldType = 'manyToOne|Test\Entity|Test\TargetEntity|owningSideField||';
        $additionalFieldOptions = [];

        list($resultFieldType, $resultFieldOptions) = $this->configHelper->createFieldOptions(
            $extendEntityConfig,
            $fieldType,
            $additionalFieldOptions
        );
        $this->assertEquals('oneToMany', $resultFieldType);
        $this->assertEquals(
            [
                'extend' => [
                    'is_extend' => true,
                    'origin'        => ExtendScope::ORIGIN_CUSTOM,
                    'owner'         => ExtendScope::OWNER_CUSTOM,
                    'state'         => ExtendScope::STATE_NEW,
                    'relation_key'  => 'manyToOne|Test\Entity|Test\TargetEntity|owningSideField',
                    'target_entity' => 'Test\TargetEntity',
                ]
            ],
            $resultFieldOptions
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The field type "item1||item2" is not supported.
     */
    public function testCreateFieldOptionsForNotSupportedFieldType()
    {
        $extendEntityConfig = $this->getEntityConfig('Test\Entity', []);
        $fieldType = 'item1||item2';
        $additionalFieldOptions = [];

        $this->configHelper->createFieldOptions($extendEntityConfig, $fieldType, $additionalFieldOptions);
    }

    public function testGetEntityConfig()
    {
        $className = 'Oro\Bundle\SomeBundle\Entity\SomeEntity';
        $scope = 'scope';

        $entityConfigModel = $this->createMock(EntityConfigModel::class);
        $entityConfigModel
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn($className);

        $entityConfig = $this->createMock(ConfigInterface::class);

        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($entityConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($configProvider);

        $this->assertEquals($entityConfig, $this->configHelper->getEntityConfig($entityConfigModel, $scope));
    }

    public function testGetNonExtendedEntitiesClasses()
    {
        $entitiesConfig = [
            $this->getEntityConfig('extended_1', ['is_extend' => true]),
            $this->getEntityConfig('extended_2', ['is_extend' => true]),
            $this->getEntityConfig('not_extended_1', ['is_extend' => false]),
            $this->getEntityConfig('not_extended_2', ['is_extend' => false]),
        ];

        $this->configManager
            ->expects($this->once())
            ->method('getConfigs')
            ->willReturn($entitiesConfig);

        $this->assertEquals(['not_extended_1', 'not_extended_2'], $this->configHelper->getNonExtendedEntitiesClasses());
    }

    private function expectsGetClassNameAndFieldName()
    {
        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getFieldName')
            ->willReturn(self::FIELD_NAME);

        $entityConfigModel = $this->createMock(EntityConfigModel::class);
        $entityConfigModel
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn(self::ENTITY_CLASS_NAME);

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entityConfigModel);
    }

    /**
     * @param string $scope
     * @param ConfigInterface $returnedConfig
     * @return ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectsGetProviderByScope($scope, ConfigInterface $returnedConfig)
    {
        $configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME, self::FIELD_NAME)
            ->willReturn($returnedConfig);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with($scope)
            ->willReturn($configProvider);

        return $configProvider;
    }

    public function testUpdateFieldConfigsWhenNothingHasChanged()
    {
        $options = [
            'extend' => [
                'state' => ExtendScope::STATE_ACTIVE
            ],
        ];

        $this->expectsGetClassNameAndFieldName();

        $config = $this->createMock(ConfigInterface::class);
        $configProvider = $this->expectsGetProviderByScope('extend', $config);
        $configProvider
            ->expects($this->never())
            ->method('getPropertyConfig');

        $config
            ->expects($this->once())
            ->method('is')
            ->with('state', ExtendScope::STATE_ACTIVE)
            ->willReturn(true);

        $this->configManager
            ->expects($this->never())
            ->method('persist');

        $this->fieldConfigModel
            ->expects($this->never())
            ->method('fromArray');

        $this->configHelper->updateFieldConfigs($this->fieldConfigModel, $options);
    }

    public function testUpdateFieldConfigsWhenOptionValueHasChanged()
    {
        $options = [
            'extend' => [
                'state' => ExtendScope::STATE_ACTIVE
            ],
        ];
        $all = [
            'state' => ExtendScope::STATE_ACTIVE
        ];
        $indexedValues = ['state' => true];
        $scope = 'extend';

        $this->expectsGetClassNameAndFieldName();

        $config = $this->createMock(ConfigInterface::class);
        $configProvider = $this->expectsGetProviderByScope($scope, $config);

        $propertyConfigContainer = $this->getMockBuilder(PropertyConfigContainer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configProvider
            ->expects($this->once())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfigContainer);

        $config
            ->expects($this->once())
            ->method('is')
            ->with('state', ExtendScope::STATE_ACTIVE)
            ->willReturn(false);

        $config
            ->expects($this->once())
            ->method('set')
            ->with('state', ExtendScope::STATE_ACTIVE);

        $config
            ->expects($this->once())
            ->method('all')
            ->willReturn($all);

        $configId = $this->createMock(ConfigIdInterface::class);

        $configId
            ->expects($this->once())
            ->method('getScope')
            ->willReturn($scope);

        $config
            ->expects($this->exactly(2))
            ->method('getId')
            ->willReturn($configId);

        $propertyConfigContainer
            ->expects($this->once())
            ->method('getIndexedValues')
            ->with($configId)
            ->willReturn($indexedValues);

        $this->configManager
            ->expects($this->once())
            ->method('persist')
            ->with($config);

        $this->fieldConfigModel
            ->expects($this->once())
            ->method('fromArray')
            ->with($scope, $all, $indexedValues);

        $this->configHelper->updateFieldConfigs($this->fieldConfigModel, $options);
    }
}
