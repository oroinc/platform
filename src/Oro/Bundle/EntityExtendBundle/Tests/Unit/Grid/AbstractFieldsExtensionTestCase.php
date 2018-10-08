<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Grid\AbstractFieldsExtension;
use Oro\Bundle\EntityExtendBundle\Grid\FieldsHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

abstract class AbstractFieldsExtensionTestCase extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'Test\Entity';
    const ENTITY_NAME = 'Test:Entity';
    const FIELD_NAME = 'testField';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityClassResolver;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $viewConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $datagridConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $attributeConfigProvider;

    /** @var FieldsHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $fieldsHelper;

    /**
     * @return AbstractFieldsExtension
     */
    abstract protected function getExtension();

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturnMap(
                [
                    [self::ENTITY_CLASS, self::ENTITY_CLASS],
                    [self::ENTITY_NAME, self::ENTITY_CLASS],
                    [\stdClass::class, \stdClass::class],
                ]
            );

        $this->entityConfigProvider = $this->getConfigProviderMock();
        $this->extendConfigProvider = $this->getConfigProviderMock();
        $this->datagridConfigProvider = $this->getConfigProviderMock();
        $this->viewConfigProvider = $this->getConfigProviderMock();
        $this->attributeConfigProvider = $this->getConfigProviderMock();

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', $this->entityConfigProvider],
                        ['extend', $this->extendConfigProvider],
                        ['datagrid', $this->datagridConfigProvider],
                        ['view', $this->viewConfigProvider],
                        ['attribute', $this->attributeConfigProvider],
                    ]
                )
            );

        $this->fieldsHelper = $this->getMockBuilder(FieldsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->configManager,
            $this->entityClassResolver,
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->datagridConfigProvider,
            $this->viewConfigProvider,
            $this->fieldsHelper
        );
    }

    /**
     * @param bool $isEnabled
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFeatureCheckerMock($isEnabled = true)
    {
        $checker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $checker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn($isEnabled);

        return $checker;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testProcessConfigsNoFields()
    {
        $this->fieldsHelper->expects($this->any())
            ->method('getFields')
            ->willReturn([]);

        $config = $this->getDatagridConfiguration();
        $this->getExtension()->processConfigs($config);
    }

    /**
     * @param array $options
     * @return DatagridConfiguration
     */
    abstract protected function getDatagridConfiguration(array $options = []);

    public function testProcessConfigs()
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
                                'data_name' => 'o.'.self::FIELD_NAME,
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'type' => 'string',
                                'data_name' => 'o.'.self::FIELD_NAME,
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

    public function testProcessConfigsWithFrom()
    {
        $fieldType = 'string';

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        $from = [
            ['table' => '\stdClass', 'alias' => 'std'],
            ['table' => self::ENTITY_CLASS, 'alias' => 'e'],
        ];
        $config = $this->getDatagridConfiguration(['source' => ['query' => ['from' => $from]]]);
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
                                'data_name' => 'e.'.self::FIELD_NAME,
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'type' => 'string',
                                'data_name' => 'e.'.self::FIELD_NAME,
                                'enabled' => true,
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

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $fieldType
     * @param array $extendFieldConfig
     */
    abstract protected function setExpectationForGetFields(
        $className,
        $fieldName,
        $fieldType,
        array $extendFieldConfig = []
    );

    public function testProcessConfigsForEnum()
    {
        $fieldType = 'enum';
        $alias = 'c';

        $targetFieldName = 'testRel';
        $relAlias = 'auto_rel_1';

        $this->setExpectationForGetFields(
            self::ENTITY_CLASS,
            self::FIELD_NAME,
            $fieldType,
            [
                'target_field' => $targetFieldName,
            ]
        );

        $from = [
            ['table' => '\stdClass', 'alias' => 'std'],
            ['table' => self::ENTITY_CLASS, 'alias' => $alias],
        ];
        $config = $this->getDatagridConfiguration(['source' => ['query' => ['from' => $from]]]);

        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        $this->assertEquals(
            array_merge(
                $initialConfig,
                [
                    'source' => [
                        'query' => [
                            'from' => $from,
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'c.testField',
                                        'alias' => 'auto_rel_1',
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
                            'order' => 0
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
                                'data_name' => $alias.'.'.self::FIELD_NAME,
                                'type' => 'enum',
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'fields_acl' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => $alias.'.'.self::FIELD_NAME,
                            ],
                        ],
                    ],
                ]
            ),
            $config->toArray()
        );
    }

    public function testProcessConfigsForMultiEnum()
    {
        $fieldType = 'multiEnum';
        $alias = 'c';

        $targetFieldName = 'testRel';

        $this->setExpectationForGetFields(
            self::ENTITY_CLASS,
            self::FIELD_NAME,
            $fieldType,
            [
                'target_field' => $targetFieldName,
            ]
        );

        $from = [
            ['table' => '\stdClass', 'alias' => 'std'],
            ['table' => self::ENTITY_CLASS, 'alias' => $alias],
        ];
        $config = $this->getDatagridConfiguration(['source' => ['query' => ['from' => $from]]]);

        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        $this->assertEquals(
            array_merge(
                $initialConfig,
                [
                    'source' => [
                        'query' => [
                            'from' => $from,
                            'select' => ['c.testFieldSnapshot'],
                        ],
                    ],
                    'columns' => [
                        self::FIELD_NAME => [
                            'data_name' => 'testFieldSnapshot',
                            'frontend_type' => 'multiEnum',
                            'label' => 'label',
                            'renderable' => true,
                            'required' => false,
                            'order' => 0
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => 'c.testFieldSnapshot',
                            ],
                        ],
                    ],
                    'filters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => 'c.testField',
                                'type' => 'multiEnum',
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'fields_acl' => [
                        'columns' => [
                            'testFieldSnapshot' => [
                                'data_name' => 'c.testField',
                            ],
                        ],
                    ],
                ]
            ),
            $config->toArray()
        );
    }

    public function testProcessConfigsToOne()
    {
        $fieldType = 'manyToOne';

        $this->setExpectationForGetFields(
            self::ENTITY_CLASS,
            self::FIELD_NAME,
            $fieldType,
            [
                'target_field' => 'name',
            ]
        );

        $config = $this->getDatagridConfiguration(['source' => ['query' => ['groupBy' => 'o.someField']]]);
        $initialConfig = $config->toArray();
        $this->getExtension()->processConfigs($config);
        $this->assertEquals(
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
                            'order' => 0
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
                                'enabled' => true,
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
