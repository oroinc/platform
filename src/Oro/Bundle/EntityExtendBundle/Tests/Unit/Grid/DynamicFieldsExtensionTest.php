<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Provider\SelectedFields\SelectedFieldsProviderInterface;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;
use Oro\Component\Testing\Unit\EntityTrait;

class DynamicFieldsExtensionTest extends AbstractFieldsExtensionTestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var Config */
    private $attributeEntityConfig;

    /** @var SelectedFieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $selectedFieldsProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->attributeEntityConfig = new Config(new EntityConfigId('attribute', self::ENTITY_NAME));
        $this->selectedFieldsProvider = $this->createMock(SelectedFieldsProviderInterface::class);

        $this->selectedFieldsProvider
            ->expects(self::any())
            ->method('getSelectedFields')
            ->with($this->isInstanceOf(DatagridConfiguration::class), $this->isInstanceOf(ParameterBag::class))
            ->willReturn([self::FIELD_NAME]);
    }


    /** {@inheritdoc} */
    protected function getExtension()
    {
        $extension = new DynamicFieldsExtension(
            $this->configManager,
            $this->entityClassResolver,
            new DatagridGuesserMock(),
            $this->fieldsHelper,
            $this->doctrineHelper,
            $this->selectedFieldsProvider
        );

        $extension->setParameters(new ParameterBag());

        return $extension;
    }

    public function testIsApplicable()
    {
        $this->assertFalse(
            $this->getExtension()->isApplicable(
                DatagridConfiguration::create(
                    [
                        'source' => [
                            'type' => 'orm',
                        ],
                    ]
                )
            )
        );
        $this->assertFalse(
            $this->getExtension()->isApplicable(
                DatagridConfiguration::create(
                    [
                        'extended_entity_name' => 'entity',
                    ]
                )
            )
        );
    }

    public function testIsApplicableWhenHasNoConfig()
    {
        $datagridConfig = DatagridConfiguration::create([
            'extended_entity_name' => self::ENTITY_NAME,
            'source' => [
                'type' => 'orm',
            ],
        ]);

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(false);

        $this->assertFalse($this->getExtension()->isApplicable($datagridConfig));
    }

    /**
     * @return array
     */
    public function isExtendDataProvider()
    {
        return [
            'is applicable' => [
                'isExtend' => true
            ],
            'not applicable' => [
                'isExtend' => false
            ],
        ];
    }

    /**
     * @dataProvider isExtendDataProvider
     *
     * @param bool $isExtend
     */
    public function testIsApplicableIfEntityIsExtendable($isExtend)
    {
        $datagridConfig = DatagridConfiguration::create([
            'extended_entity_name' => self::ENTITY_NAME,
            'source' => [
                'type' => 'orm',
            ],
        ]);

        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('is')
            ->with('is_extend')
            ->willReturn($isExtend);

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($config);

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn(true);

        $this->assertEquals($isExtend, $this->getExtension()->isApplicable($datagridConfig));
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            300,
            $this->getExtension()->getPriority()
        );
    }

    public function testProcessConfigsWithVisibleFilter()
    {
        $fieldType = 'string';

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        $config = $this->getDatagridConfiguration();
        $initialConfig = $config->toArray();

        $this->getExtension()->processConfigs($config);
        $this->assertEquals(
            array_merge(
                $initialConfig,
                [
                    'columns' => [
                        self::FIELD_NAME => [
                            'label' => 'label',
                            'frontend_type' => 'string',
                            'renderable' => true,
                            'required' => false,
                            'data_name' => 'testField',
                            'order' => 0
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => 'o.' . self::FIELD_NAME,
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'type' => 'string',
                                'data_name' => 'o.' . self::FIELD_NAME,
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'source' => [
                        'query' => [
                            'from' => [['table' => self::ENTITY_CLASS, 'alias' => 'o']],
                            'select' => ['o.testField'],
                        ],
                    ],
                    'fields_acl' => ['columns' => ['testField' => ['data_name' => 'o.testField']]],
                ]
            ),
            $config->toArray()
        );
    }

    /** {@inheritdoc} */
    protected function getDatagridConfiguration(array $options = [])
    {
        return DatagridConfiguration::create(array_merge($options, ['extended_entity_name' => self::ENTITY_NAME]));
    }

    /** {@inheritdoc} */
    protected function setExpectationForGetFields($className, $fieldName, $fieldType, array $extendFieldConfig = [])
    {
        // Assume that entity cannot have attributes.
        $this->attributeEntityConfig->set('has_attributes', false);

        $fieldId = new FieldConfigId('entity', $className, $fieldName, $fieldType);

        $extendConfig = new Config(new FieldConfigId('extend', $className, $fieldName, $fieldType));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        foreach ($extendFieldConfig as $key => $value) {
            $extendConfig->set($key, $value);
        }
        $extendConfig->set('is_deleted', false);

        $entityFieldConfig = new Config(new FieldConfigId('entity', $className, $fieldName, $fieldType));
        $entityFieldConfig->set('label', 'label');

        $datagridFieldConfig = new Config(
            new FieldConfigId('datagrid', $className, $fieldName, $fieldType)
        );
        $datagridFieldConfig->set('show_filter', true);
        $datagridFieldConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $viewFieldConfig = new Config(
            new FieldConfigId('view', $className, $fieldName, $fieldType)
        );

        $this->fieldsHelper->expects($this->any())
            ->method('getFields')
            ->willReturn([$fieldId]);

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($entityFieldConfig));

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($extendConfig));

        $this->datagridConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($datagridFieldConfig));

        $this->viewConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->will($this->returnValue($viewFieldConfig));

        $this->configManager->expects($this->any())
            ->method('getEntityConfig')
            ->with('attribute', $className)
            ->willReturn($this->attributeEntityConfig);
    }

    public function testProcessConfigsWhenNoAttributes()
    {
        $fieldType = 'string';

        $this->mockAttributeFieldConfig($fieldType, false);

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        // Entity can have attributes.
        $this->attributeEntityConfig->set('has_attributes', true);

        $config = $this->getDatagridConfiguration();

        $this->getExtension()->processConfigs($config);
        $this->assertEquals($this->getExpectedConfig($config), $config->toArray());
    }

    public function testProcessConfigsWhenAttributesInFamilies()
    {
        $fieldType = 'string';

        $attributeFieldConfig = $this->mockAttributeFieldConfig($fieldType, true);
        $this->mockAttributeInFamilies($attributeFieldConfig, [1 => [1]]);

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        // Entity can have attributes.
        $this->attributeEntityConfig->set('has_attributes', true);

        $config = $this->getDatagridConfiguration();

        $this->getExtension()->processConfigs($config);
        $this->assertEquals($this->getExpectedConfig($config), $config->toArray());
    }

    public function testProcessConfigsWhenAttributesNotInFamilies()
    {
        $fieldType = 'string';

        $attributeFieldConfig = $this->mockAttributeFieldConfig($fieldType, true);
        $this->mockAttributeInFamilies($attributeFieldConfig, []);

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        // Entity can have attributes.
        $this->attributeEntityConfig->set('has_attributes', true);

        $config = $this->getDatagridConfiguration();
        $initialConfig = $config->toArray();

        $this->getExtension()->processConfigs($config);
        $this->assertEquals($initialConfig, $config->toArray());
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return array
     */
    private function getExpectedConfig(DatagridConfiguration $config): array
    {
        $expectedConfig = [
            'columns' => [
                self::FIELD_NAME => [
                    'label' => 'label',
                    'frontend_type' => 'string',
                    'renderable' => true,
                    'required' => false,
                    'data_name' => 'testField',
                    'order' => 0
                ],
            ],
            'sorters' => [
                'columns' => [
                    self::FIELD_NAME => [
                        'data_name' => 'o.' . self::FIELD_NAME,
                    ],
                ],
            ],
            'filters' => [
                'columns' => [
                    self::FIELD_NAME => [
                        'type' => 'string',
                        'data_name' => 'o.' . self::FIELD_NAME,
                        'enabled' => true,
                    ],
                ],
            ],
            'source' => [
                'query' => [
                    'from' => [['table' => self::ENTITY_CLASS, 'alias' => 'o']],
                    'select' => ['o.testField'],
                ],
            ],
            'fields_acl' => ['columns' => ['testField' => ['data_name' => 'o.testField']]],
        ];

        return $this->mergeWithInitialConfig($config, $expectedConfig);
    }

    /**
     * @param Config $attributeFieldConfig
     * @param array $familiesIdsByAttributesIds
     */
    private function mockAttributeInFamilies(Config $attributeFieldConfig, array $familiesIdsByAttributesIds): void
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $attributeFieldConfig->getId();
        $fieldConfigModel = $this->getEntity(
            FieldConfigModel::class,
            ['id' => 1, 'fieldName' => $fieldConfigId->getFieldName(), 'type' => $fieldConfigId->getFieldType()]
        );

        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($fieldConfigModel);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(AttributeFamily::class)
            ->willReturn($attributeFamilyRepository = $this->createMock(AttributeFamilyRepository::class));

        $attributeFamilyRepository->expects($this->once())
            ->method('getFamilyIdsForAttributes')
            ->with([$fieldConfigModel])
            ->willReturn($familiesIdsByAttributesIds);
    }

    /**
     * @param string $fieldType
     * @param bool $isAttribute
     *
     * @return Config
     */
    private function mockAttributeFieldConfig(string $fieldType, bool $isAttribute): Config
    {
        $attributeFieldConfig = new Config(
            new FieldConfigId('attribute', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType)
        );
        $attributeFieldConfig->set('is_attribute', $isAttribute);

        $this->attributeConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($attributeFieldConfig);

        return $attributeFieldConfig;
    }

    public function testBuildExpressionWhenNoRelevantFields()
    {
        $selectedFieldsProvider = $this->createMock(SelectedFieldsProviderInterface::class);
        $extension = new DynamicFieldsExtension(
            $this->configManager,
            $this->entityClassResolver,
            new DatagridGuesserMock(),
            $this->fieldsHelper,
            $this->doctrineHelper,
            $selectedFieldsProvider
        );
        $extension->setParameters(new ParameterBag());

        $config = $this->getDatagridConfiguration();
        $expectedConfigArray = $this->mergeWithInitialConfig($config, []);

        $selectedFieldsProvider
            ->expects(self::once())
            ->method('getSelectedFields')
            ->with($config, $extension->getParameters())
            ->willReturn([]);

        $fieldConfigId = new FieldConfigId('attribute', self::ENTITY_CLASS, self::FIELD_NAME, 'string');
        $extension->buildExpression([$fieldConfigId], $config, 'o');
        $this->assertEquals($expectedConfigArray, $config->toArray());
    }

    /**
     * @dataProvider buildExpressionDataProvider
     *
     * @param FieldConfigId[] $fieldsConfigIds
     * @param array $configArray
     */
    public function testBuildExpressionWhenFieldNotRelevant(array $fieldsConfigIds, array $configArray)
    {
        $extension = $this->getExtension();

        $config = $this->getDatagridConfiguration();
        $expectedConfigArray = $this->mergeWithInitialConfig($config, $configArray);

        // Assumes that 1st field returned by buildExpressionDataProvider is always relevant.
        $this->setExpectationForGetFields(
            $fieldsConfigIds[0]->getClassName(),
            $fieldsConfigIds[0]->getFieldName(),
            $fieldsConfigIds[0]->getFieldType()
        );

        $extension->buildExpression($fieldsConfigIds, $config, 'o');
        $this->assertEquals($expectedConfigArray, $config->toArray());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildExpressionDataProvider(): array
    {
        return [
            'string type' => [
                'fieldsConfigIds' => [
                    new FieldConfigId('attribute', self::ENTITY_CLASS, self::FIELD_NAME, 'string'),
                    new FieldConfigId('attribute', self::ENTITY_CLASS, 'notRelevantField', 'string'),
                ],
                'configArray' => [
                    'source' => ['query' => ['select' => ['o.' . self::FIELD_NAME]]],
                    'columns' => [self::FIELD_NAME => ['data_name' => self::FIELD_NAME]],
                    'sorters' => ['columns' => [self::FIELD_NAME => ['data_name' => 'o.' . self::FIELD_NAME]]],
                    'filters' => ['columns' => [self::FIELD_NAME => ['data_name' => 'o.' . self::FIELD_NAME]]],
                    'fields_acl' => ['columns' => [self::FIELD_NAME => ['data_name' => 'o.' . self::FIELD_NAME]]],
                ],
            ],
            'enum type' => [
                'fieldsConfigIds' => [
                    new FieldConfigId('attribute', self::ENTITY_CLASS, self::FIELD_NAME, 'enum'),
                    new FieldConfigId('attribute', self::ENTITY_CLASS, 'notRelevantField', 'enum'),
                ],
                'configArray' => [
                    'source' => [
                        'query' => [
                            'join' => ['left' => [['join' => 'o.' . self::FIELD_NAME, 'alias' => 'auto_rel_1']]],
                            'select' => [
                                'IDENTITY(o.' . self::FIELD_NAME . ') as ' . self::FIELD_NAME,
                                'auto_rel_1. as auto_rel_1_',
                            ],
                        ],
                    ],
                    'columns' => [self::FIELD_NAME => ['data_name' => self::FIELD_NAME]],
                    'sorters' => ['columns' => [self::FIELD_NAME => ['data_name' => 'auto_rel_1_']]],
                    'filters' => ['columns' => [self::FIELD_NAME => ['data_name' => 'o.' . self::FIELD_NAME]]],
                    'fields_acl' => ['columns' => [self::FIELD_NAME => ['data_name' => 'o.' . self::FIELD_NAME]]],
                ],
            ],
            'multiEnum type' => [
                'fieldsConfigIds' => [
                    new FieldConfigId('attribute', self::ENTITY_CLASS, self::FIELD_NAME, 'multiEnum'),
                    new FieldConfigId('attribute', self::ENTITY_CLASS, 'notRelevantField', 'multiEnum'),
                ],
                'expectedConfigArray' => [
                    'extended_entity_name' => 'Test:Entity',
                    'source' => ['query' => ['select' => ['o.' . self::FIELD_NAME . 'Snapshot']]],
                    'columns' => [self::FIELD_NAME => ['data_name' => self::FIELD_NAME . 'Snapshot']],
                    'sorters' => [
                        'columns' => [self::FIELD_NAME => ['data_name' => 'o.' . self::FIELD_NAME . 'Snapshot']],
                    ],
                    'filters' => ['columns' => [self::FIELD_NAME => ['data_name' => 'o.' . self::FIELD_NAME]]],
                    'fields_acl' => [
                        'columns' => [self::FIELD_NAME . 'Snapshot' => ['data_name' => 'o.' . self::FIELD_NAME]],
                    ],
                ],
            ],
            'manyToOne type' => [
                'fieldsConfigIds' => [
                    new FieldConfigId('attribute', self::ENTITY_CLASS, self::FIELD_NAME, 'manyToOne'),
                    new FieldConfigId('attribute', self::ENTITY_CLASS, 'notRelevantField', 'manyToOne'),
                ],
                'expectedConfigArray' => [
                    'extended_entity_name' => 'Test:Entity',
                    'source' => [
                        'query' => [
                            'join' => ['left' => [['join' => 'o.' . self::FIELD_NAME, 'alias' => 'auto_rel_1']]],
                            'select' => [
                                'IDENTITY(o.' . self::FIELD_NAME . ') as ' . self::FIELD_NAME . '_identity',
                                'auto_rel_1.id as ' . self::FIELD_NAME . '_target_field',
                            ],
                        ],
                    ],
                    'columns' => [self::FIELD_NAME => ['data_name' => self::FIELD_NAME . '_target_field']],
                    'sorters' => [
                        'columns' => [self::FIELD_NAME => ['data_name' => self::FIELD_NAME . '_target_field']],
                    ],
                    'filters' => [
                        'columns' => [self::FIELD_NAME => ['data_name' => 'IDENTITY(o.' . self::FIELD_NAME . ')']],
                    ],
                    'fields_acl' => [
                        'columns' => [self::FIELD_NAME . '_target_field' => ['data_name' => 'o.' . self::FIELD_NAME]],
                    ],
                ],
            ],
            'oneToOne type' => [
                'fieldsConfigIds' => [
                    new FieldConfigId('attribute', self::ENTITY_CLASS, self::FIELD_NAME, 'oneToOne'),
                    new FieldConfigId('attribute', self::ENTITY_CLASS, 'notRelevantField', 'oneToOne'),
                ],
                'expectedConfigArray' => [
                    'extended_entity_name' => 'Test:Entity',
                    'source' => [
                        'query' => [
                            'join' => ['left' => [['join' => 'o.' . self::FIELD_NAME, 'alias' => 'auto_rel_1']]],
                            'select' => [
                                'IDENTITY(o.' . self::FIELD_NAME . ') as ' . self::FIELD_NAME . '_identity',
                                'auto_rel_1.id as ' . self::FIELD_NAME . '_target_field',
                            ],
                        ],
                    ],
                    'columns' => [self::FIELD_NAME => ['data_name' => self::FIELD_NAME . '_target_field']],
                    'sorters' => [
                        'columns' => [self::FIELD_NAME => ['data_name' => self::FIELD_NAME . '_target_field']],
                    ],
                    'filters' => [
                        'columns' => [self::FIELD_NAME => ['data_name' => 'IDENTITY(o.' . self::FIELD_NAME . ')']],
                    ],
                    'fields_acl' => [
                        'columns' => [self::FIELD_NAME . '_target_field' => ['data_name' => 'o.' . self::FIELD_NAME]],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param DatagridConfiguration $config
     * @param array $expectedConfig
     *
     * @return array
     */
    private function mergeWithInitialConfig(DatagridConfiguration $config, array $expectedConfig): array
    {
        $initialConfig = $config->toArray();

        return array_merge($initialConfig, $expectedConfig);
    }
}
