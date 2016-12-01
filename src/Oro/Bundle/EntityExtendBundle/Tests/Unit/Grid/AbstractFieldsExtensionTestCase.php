<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Grid\AbstractFieldsExtension;

abstract class AbstractFieldsExtensionTestCase extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Test\Entity';
    const ENTITY_NAME = 'Test:Entity';
    const FIELD_NAME = 'testField';

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var EntityClassResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassResolver;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $viewConfigProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $datagridConfigProvider;

    /**
     * @return AbstractFieldsExtension
     */
    abstract protected function getExtension();

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
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

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', $this->entityConfigProvider],
                        ['extend', $this->extendConfigProvider],
                        ['datagrid', $this->datagridConfigProvider],
                        ['view', $this->viewConfigProvider],
                    ]
                )
            );
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

    public function testProcessConfigsNoFields()
    {
        $this->configManager->expects($this->once())->method('hasConfig')->willReturn(false);
        $this->configManager->expects($this->never())->method('getConfig');

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
                            'select' => ['IDENTITY(c.testField) as testField'],
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
                                'data_name' => $relAlias.'.'.$targetFieldName,
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

    public function testProcessConfigsForNotConfigurableEntity()
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY_CLASS)
            ->will($this->returnValue(false));

        $config = $this->getDatagridConfiguration();
        $this->getExtension()->processConfigs($config);
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
                            'data_name' => 'testField_data',
                        ],
                    ],
                    'sorters' => [
                        'columns' => [
                            self::FIELD_NAME => [
                                'data_name' => 'testField_data',
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
                                    'field_type' => 'entity',
                                    'field_options' => [
                                        'class' => null,
                                        'property' => 'name',
                                        'multiple' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'source' => [
                        'query' => [
                            'from' => [['table' => self::ENTITY_CLASS, 'alias' => 'o']],
                            'select' => ['testField.name as testField_data'],
                            'join' => [
                                'left' => [
                                    [
                                        'join' => 'o.testField',
                                        'alias' => 'testField',
                                    ],
                                ],
                            ],
                            'groupBy' => 'o.someField,testField.name'
                        ],
                    ],
                    'fields_acl' => ['columns' => ['testField_data' => ['data_name' => 'o.testField']]],
                ]
            ),
            $config->toArray()
        );
    }
}
