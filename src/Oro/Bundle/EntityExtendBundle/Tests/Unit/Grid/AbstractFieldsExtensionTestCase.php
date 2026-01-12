<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Grid\AbstractFieldsExtension;
use Oro\Bundle\EntityExtendBundle\Grid\FieldsHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

abstract class AbstractFieldsExtensionTestCase extends TestCase
{
    protected const ENTITY_CLASS = 'Test\Entity';
    protected const ENTITY_NAME = 'Test:Entity';
    protected const FIELD_NAME = 'testField';

    protected ConfigManager&MockObject $configManager;
    protected EntityClassResolver&MockObject $entityClassResolver;
    protected ConfigProvider&MockObject $entityConfigProvider;
    protected ConfigProvider&MockObject $extendConfigProvider;
    protected ConfigProvider&MockObject $viewConfigProvider;
    protected ConfigProvider&MockObject $datagridConfigProvider;
    protected ConfigProvider&MockObject $attributeConfigProvider;
    protected FieldsHelper&MockObject $fieldsHelper;

    abstract protected function getExtension(): AbstractFieldsExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);

        $this->entityClassResolver->expects(self::any())
            ->method('getEntityClass')
            ->willReturnMap([
                [self::ENTITY_CLASS, self::ENTITY_CLASS],
                [self::ENTITY_NAME, self::ENTITY_CLASS],
                [\stdClass::class, \stdClass::class],
            ]);

        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->datagridConfigProvider = $this->createMock(ConfigProvider::class);
        $this->viewConfigProvider = $this->createMock(ConfigProvider::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);

        $this->configManager->expects(self::any())
            ->method('getProvider')
            ->willReturnMap([
                ['entity', $this->entityConfigProvider],
                ['extend', $this->extendConfigProvider],
                ['datagrid', $this->datagridConfigProvider],
                ['view', $this->viewConfigProvider],
                ['attribute', $this->attributeConfigProvider],
            ]);

        $this->fieldsHelper = $this->createMock(FieldsHelper::class);
    }

    public function testProcessConfigsNoFields(): void
    {
        $this->fieldsHelper->expects(self::any())
            ->method('getFields')
            ->willReturn([]);

        $config = $this->getDatagridConfiguration();
        $this->getExtension()->processConfigs($config);
    }

    abstract protected function getDatagridConfiguration(array $options = []): DatagridConfiguration;

    public function testProcessConfigsWithDatagridOrder(): void
    {
        $fieldType = 'string';

        $datagridFieldConfig = new Config(
            new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType),
            [
                'show_filter' => true,
                'is_visible' => DatagridScope::IS_VISIBLE_TRUE,
                'renderable' => true,
                'order' => 3,
            ]
        );

        $this->datagridConfigProvider->expects(self::any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->willReturn($datagridFieldConfig);

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        $config = $this->getDatagridConfiguration();
        $initialConfig = $config->toArray();

        $this->getExtension()->processConfigs($config);
        self::assertEquals(
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
                            'order' => 3
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
                                'renderable' => true,
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

    public function testProcessConfigs(): void
    {
        $fieldType = 'string';

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        $config = $this->getDatagridConfiguration();
        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        self::assertEquals(
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
                                'renderable' => true,
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

    public function testProcessConfigsWithFrom(): void
    {
        $fieldType = 'string';

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        $from = [
            ['table' => \stdClass::class, 'alias' => 'std'],
            ['table' => self::ENTITY_CLASS, 'alias' => 'e'],
        ];
        $config = $this->getDatagridConfiguration(['source' => ['query' => ['from' => $from]]]);
        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        self::assertEquals(
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
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => 'e.' . self::FIELD_NAME,
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'type' => 'string',
                                'data_name' => 'e.' . self::FIELD_NAME,
                                'renderable' => true,
                            ],
                        ],
                    ],
                    'source' => [
                        'query' => [
                            'from' => $from,
                            'select' => ['e.testField'],
                        ],
                    ],
                    'fields_acl' => ['columns' => ['testField' => ['data_name' => 'e.testField']]],
                ]
            ),
            $config->toArray()
        );
    }

    abstract protected function setExpectationForGetFields(
        string $className,
        string $fieldName,
        string $fieldType,
        array $extendFieldConfig = []
    ): void;

    public function testProcessConfigsForEnum(): void
    {
        $fieldType = 'enum';
        $alias = 'c';

        $targetFieldName = 'testRel';

        $this->setExpectationForGetFields(
            self::ENTITY_CLASS,
            self::FIELD_NAME,
            $fieldType,
            ['target_field' => $targetFieldName]
        );

        $from = [
            ['table' => \stdClass::class, 'alias' => 'std'],
            ['table' => self::ENTITY_CLASS, 'alias' => $alias],
        ];
        $config = $this->getDatagridConfiguration(['source' => ['query' => ['from' => $from]]]);

        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        self::assertEquals(
            array_merge(
                $initialConfig,
                [
                    'source' => [
                        'query' => [
                            'from' => $from,
                            'join' => [
                                'left' => [
                                    [
                                        'join' => EnumOption::class,
                                        'alias' => 'auto_rel_1',
                                        'conditionType' => 'WITH',
                                        'condition' => "JSON_EXTRACT(order1.serialized_data, 'testField') = auto_rel_1"
                                    ],
                                ],
                            ],
                            'select' => [
                                'IDENTITY(c.testField) as testField',
                                'auto_rel_1.testRel as auto_rel_1_testRel',
                            ],
                        ],
                    ],
                    'columns' => [
                        self::FIELD_NAME => [
                            'data_name' => self::FIELD_NAME,
                            'frontend_type' => 'enum',
                            'label' => 'label',
                            'renderable' => true,
                            'required' => false,
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => 'auto_rel_1_testRel',
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => $alias . '.' . self::FIELD_NAME,
                                'type' => 'enum',
                                'renderable' => true,
                            ],
                        ],
                    ],
                    'fields_acl' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => $alias . '.' . self::FIELD_NAME,
                            ],
                        ],
                    ],
                ]
            ),
            $config->toArray()
        );
    }

    public function testProcessConfigsForMultiEnum(): void
    {
        $fieldType = 'multiEnum';
        $alias = 'c';

        $targetFieldName = 'testRel';

        $this->setExpectationForGetFields(
            self::ENTITY_CLASS,
            self::FIELD_NAME,
            $fieldType,
            ['target_field' => $targetFieldName]
        );

        $from = [
            ['table' => \stdClass::class, 'alias' => 'std'],
            ['table' => self::ENTITY_CLASS, 'alias' => $alias],
        ];
        $config = $this->getDatagridConfiguration(['source' => ['query' => ['from' => $from]]]);

        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        self::assertEquals(
            array_merge(
                $initialConfig,
                [
                    'source' => [
                        'query' => [
                            'from' => $from,
                            'select' => ["JSON_EXTRACT(order1.serialized_data, 'testField') = c"],
                        ],
                    ],
                    'columns' => [
                        self::FIELD_NAME => [
                            'data_name' => 'testField',
                            'frontend_type' => 'multiEnum',
                            'label' => 'label',
                            'renderable' => true,
                            'required' => false,
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => "JSON_EXTRACT(order1.serialized_data, 'testField') = c",
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => "JSON_EXTRACT(order1.serialized_data, 'testField') = c",
                                'type' => 'multiEnum',
                                'renderable' => true,
                            ],
                        ],
                    ],
                    'fields_acl' => [
                        'columns' => [
                            'testField' => [
                                'data_name' => 'c.testField',
                            ],
                        ],
                    ],
                ]
            ),
            $config->toArray()
        );
    }

    public function testProcessConfigsToOne(): void
    {
        $fieldType = 'manyToOne';

        $this->setExpectationForGetFields(
            self::ENTITY_CLASS,
            self::FIELD_NAME,
            $fieldType,
            ['target_field' => 'name']
        );

        $config = $this->getDatagridConfiguration(['source' => ['query' => ['groupBy' => 'o.someField']]]);
        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        self::assertEquals(
            array_merge(
                $initialConfig,
                [
                    'columns' => [
                        self::FIELD_NAME => [
                            'label' => 'label',
                            'frontend_type' => 'manyToOne',
                            'renderable' => true,
                            'required' => false,
                            'data_name' => 'testField_target_field',
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => 'testField_target_field',
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'type' => 'entity',
                                'data_name' => 'IDENTITY(o.testField)',
                                'renderable' => true,
                                'translatable' => true,
                                'options' => [
                                    'field_type' => EntityType::class,
                                    'field_options' => [
                                        'class' => null,
                                        'choice_label' => 'name',
                                        'multiple' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'source' => [
                        'query' => [
                            'from' => [['table' => self::ENTITY_CLASS, 'alias' => 'o']],
                            'select' => [
                                'IDENTITY(o.testField) as testField_identity',
                                'auto_rel_1.name as testField_target_field',
                            ],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'o.testField',
                                        'alias' => 'auto_rel_1',
                                    ],
                                ],
                            ],
                            'groupBy' => 'o.someField,auto_rel_1.name'
                        ],
                    ],
                    'fields_acl' => ['columns' => ['testField_target_field' => ['data_name' => 'o.testField']]],
                ]
            ),
            $config->toArray()
        );
    }
}
