<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class PropertyConfigContainerTest extends \PHPUnit_Framework_TestCase
{
    /** @var PropertyConfigContainer */
    protected $configContainer;

    protected function setUp()
    {
        $this->configContainer = new PropertyConfigContainer([]);
    }

    public function testConfigGetterAndSetter()
    {
        $config = ['test' => 'testVal'];

        $this->configContainer->setConfig($config);
        $this->assertEquals($config, $this->configContainer->getConfig());
    }

    public function testGetItemsWithDefaultParams()
    {
        $this->configContainer->setConfig(['entity' => ['items' => ['test' => 'testVal']]]);
        $result = $this->configContainer->getItems();

        $this->assertEquals(['test' => 'testVal'], $result);
    }

    /**
     * @dataProvider getItemsProvider
     */
    public function testGetItems($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getItems($type);

        $this->assertEquals($expectedValues, $result);
    }

    /**
     * @dataProvider getDefaultValuesProvider
     */
    public function testGetDefaultValues($type, $fieldType, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getDefaultValues($type, $fieldType);

        $this->assertEquals($expectedValues, $result);
        // test that a result is cached locally
        $this->assertEquals($expectedValues, $this->configContainer->getDefaultValues($type, $fieldType));
    }

    /**
     * @dataProvider getRequiredPropertyValuesProvider
     */
    public function testGetRequiredPropertyValues($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getRequiredPropertyValues($type);

        $this->assertEquals($expectedValues, $result);
        // test that a result is cached locally
        $this->assertEquals($expectedValues, $this->configContainer->getRequiredPropertyValues($type));
    }

    /**
     * @dataProvider getNotAuditableValuesProvider
     */
    public function testGetNotAuditableValues($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getNotAuditableValues($type);

        $this->assertEquals($expectedValues, $result);
        // test that a result is cached locally
        $this->assertEquals($expectedValues, $this->configContainer->getNotAuditableValues($type));
    }

    /**
     * @dataProvider getTranslatableValuesProvider
     */
    public function testGetTranslatableValues($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getTranslatableValues($type);

        $this->assertEquals($expectedValues, $result);
        // test that a result is cached locally
        $this->assertEquals($expectedValues, $this->configContainer->getTranslatableValues($type));
    }

    /**
     * @dataProvider getIndexedValuesProvider
     */
    public function testGetIndexedValues($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getIndexedValues($type);

        $this->assertEquals($expectedValues, $result);
        // test that a result is cached locally
        $this->assertEquals($expectedValues, $this->configContainer->getIndexedValues($type));
    }

    /**
     * @dataProvider getFormItemsProvider
     */
    public function testGetFormItems($type, $fieldType, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getFormItems($type, $fieldType);

        $this->assertEquals($expectedValues, $result);
        // test that a result is cached locally
        $this->assertEquals($expectedValues, $this->configContainer->getFormItems($type, $fieldType));
    }

    /**
     * @dataProvider hasFormProvider
     */
    public function testHasForm($type, $fieldType, $config, $expectedValue)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->hasForm($type, $fieldType);

        $this->assertEquals($expectedValue, $result);
        // test that a result is cached locally
        $this->assertEquals($expectedValue, $this->configContainer->hasForm($type, $fieldType));
    }

    /**
     * @dataProvider getFormConfigProvider
     */
    public function testGetFormConfig($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getFormConfig($type);

        $this->assertEquals($expectedValues, $result);
    }

    /**
     * @dataProvider getFormBlockConfigProvider
     */
    public function testGetFormBlockConfig($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getFormBlockConfig($type);

        $this->assertEquals($expectedValues, $result);
    }

    /**
     * @dataProvider getGridActionsProvider
     */
    public function testGetGridActions($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getGridActions($type);

        $this->assertEquals($expectedValues, $result);
    }

    /**
     * @dataProvider getUpdateActionFilterProvider
     */
    public function testGetUpdateActionFilter($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getUpdateActionFilter($type);

        $this->assertEquals($expectedValues, $result);
    }

    /**
     * @dataProvider getLayoutActionsProvider
     */
    public function testGetLayoutActions($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getLayoutActions($type);

        $this->assertEquals($expectedValues, $result);
    }

    /**
     * @dataProvider getRequireJsModulesProvider
     */
    public function testGetRequireJsModules($type, $config, $expectedValues)
    {
        $this->configContainer->setConfig($config);
        $result = $this->configContainer->getRequireJsModules($type);

        $this->assertEquals($expectedValues, $result);
    }

    /**
     * @dataProvider isSchemaUpdateRequiredProvider
     */
    public function testIsSchemaUpdateRequired($code, $type, $expected)
    {
        $config = [
            'entity' => [
                'items' => [
                    'testAttr1' => [
                        'options' => [
                            'require_schema_update' => true
                        ]
                    ],
                    'testAttr2' => [
                        'options' => [
                            'require_schema_update' => false
                        ]
                    ],
                ]
            ],
            'field'  => [
                'items' => [
                    'testAttr1' => [
                        'options' => [
                            'require_schema_update' => true
                        ]
                    ],
                    'testAttr2' => [
                        'options' => [
                            'require_schema_update' => false
                        ]
                    ],
                ]
            ],
        ];
        $this->configContainer->setConfig($config);

        $this->assertEquals(
            $expected,
            $this->configContainer->isSchemaUpdateRequired($code, $type)
        );
    }

    public function getItemsProvider()
    {
        return [
            'no entity config'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                [],
            ],
            'entity config'            => [
                PropertyConfigContainer::TYPE_ENTITY,
                ['entity' => ['items' => ['test' => 'testVal']]],
                ['test' => 'testVal']
            ],
            'no field config'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [],
                [],
            ],
            'field config'             => [
                PropertyConfigContainer::TYPE_FIELD,
                ['field' => ['items' => ['test' => 'testFieldVal']]],
                ['test' => 'testFieldVal']
            ],
            'no entity config (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [],
                [],
            ],
            'entity config (by id)'    => [
                new EntityConfigId('testScope', 'Test\Cls'),
                ['entity' => ['items' => ['test' => 'testVal']]],
                ['test' => 'testVal']
            ],
            'no field config (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [],
                [],
            ],
            'field config (by id)'     => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                ['field' => ['items' => ['test' => 'testFieldVal']]],
                ['test' => 'testFieldVal']
            ],
        ];
    }

    public function getDefaultValuesProvider()
    {
        return [
            'no entity config'                     => [
                PropertyConfigContainer::TYPE_ENTITY,
                null,
                [],
                [],
            ],
            'entity config'                        => [
                PropertyConfigContainer::TYPE_ENTITY,
                null,
                [
                    'entity' => $this->getItemsForDefaultValuesTest()
                ],
                [
                    'item1' => 'value1',
                    'item2' => 'value2',
                    'item4' => 'value4',
                    'item5' => 'value5',
                ]
            ],
            'entity config (by id)'                => [
                new EntityConfigId('testScope', 'Test\Cls'),
                null,
                [
                    'entity' => $this->getItemsForDefaultValuesTest()
                ],
                [
                    'item1' => 'value1',
                    'item2' => 'value2',
                    'item4' => 'value4',
                    'item5' => 'value5',
                ]
            ],
            'field config (no field type)'         => [
                PropertyConfigContainer::TYPE_FIELD,
                null,
                [
                    'field' => $this->getItemsForDefaultValuesTest()
                ],
                [
                    'item1' => 'value1',
                    'item2' => 'value2',
                    'item4' => 'value4',
                    'item5' => 'value5',
                ]
            ],
            'field config (no field type) (by id)' => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                null,
                [
                    'field' => $this->getItemsForDefaultValuesTest()
                ],
                [
                    'item1' => 'value1',
                    'item2' => 'value2',
                    'item4' => 'value4',
                    'item5' => 'value5',
                ]
            ],
            'field config'                         => [
                PropertyConfigContainer::TYPE_FIELD,
                'string',
                [
                    'field' => $this->getItemsForDefaultValuesTest()
                ],
                [
                    'item1' => 'value1',
                    'item2' => 'value2',
                    'item5' => 'value5',
                ]
            ],
            'field config (by id)'                 => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                'string',
                [
                    'field' => $this->getItemsForDefaultValuesTest()
                ],
                [
                    'item1' => 'value1',
                    'item2' => 'value2',
                    'item5' => 'value5',
                ]
            ],
        ];
    }

    protected function getItemsForDefaultValuesTest()
    {
        return [
            'items' => [
                'item1' => [
                    'options' => [
                        'default_value' => 'value1',
                        'allowed_type'  => ['string']
                    ]
                ],
                'item2' => [
                    'options' => [
                        'default_value' => 'value2',
                    ]
                ],
                'item3' => [
                    'options' => [
                    ]
                ],
                'item4' => [
                    'options' => [
                        'default_value' => 'value4',
                        'allowed_type'  => ['int']
                    ]
                ],
                'item5' => [
                    'options' => [
                        'default_value' => 'value5',
                    ]
                ],
            ]
        ];
    }

    public function getRequiredPropertyValuesProvider()
    {
        return [
            'no entity config'                     => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                [],
            ],
            'entity config'                        => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => $this->getItemsForRequiredPropertyValuesTest()
                ],
                [
                    'item1' => ['test' => 'testVal'],
                    'item2' => [],
                ]
            ],
            'entity config (by id)'                => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => $this->getItemsForRequiredPropertyValuesTest()
                ],
                [
                    'item1' => ['test' => 'testVal'],
                    'item2' => [],
                ]
            ],
            'field config (no field type)'         => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => $this->getItemsForRequiredPropertyValuesTest()
                ],
                [
                    'item1' => ['test' => 'testVal'],
                    'item2' => [],
                ]
            ],
            'field config (no field type) (by id)' => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => $this->getItemsForRequiredPropertyValuesTest()
                ],
                [
                    'item1' => ['test' => 'testVal'],
                    'item2' => [],
                ]
            ],
        ];
    }

    protected function getItemsForRequiredPropertyValuesTest()
    {
        return [
            'items' => [
                'item1' => [
                    'options' => [
                        'required_property' => ['test' => 'testVal'],
                    ]
                ],
                'item2' => [
                    'options' => [
                        'required_property' => [],
                    ]
                ],
                'item3' => [
                    'options' => [
                    ]
                ],
            ]
        ];
    }

    public function getNotAuditableValuesProvider()
    {
        return [
            'no entity config'      => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                [],
            ],
            'entity config'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => $this->getItemsForNotAuditableValuesTest()
                ],
                [
                    'item2' => true
                ]
            ],
            'entity config (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => $this->getItemsForNotAuditableValuesTest()
                ],
                [
                    'item2' => true
                ]
            ],
            'field config'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => $this->getItemsForNotAuditableValuesTest()
                ],
                [
                    'item2' => true
                ]
            ],
            'field config (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => $this->getItemsForNotAuditableValuesTest()
                ],
                [
                    'item2' => true
                ]
            ],
        ];
    }

    protected function getItemsForNotAuditableValuesTest()
    {
        return [
            'items' => [
                'item1' => [
                    'options' => [
                        'auditable' => true,
                    ]
                ],
                'item2' => [
                    'options' => [
                        'auditable' => false,
                    ]
                ],
                'item3' => [
                    'options' => [
                    ]
                ],
                'item4' => [
                ],
            ]
        ];
    }

    public function getTranslatableValuesProvider()
    {
        return [
            'no entity config'      => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                [],
            ],
            'entity config'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => $this->getItemsForTranslatableValuesTest()
                ],
                ['item1']
            ],
            'entity config (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => $this->getItemsForTranslatableValuesTest()
                ],
                ['item1']
            ],
            'field config'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => $this->getItemsForTranslatableValuesTest()
                ],
                ['item1']
            ],
            'field config (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => $this->getItemsForTranslatableValuesTest()
                ],
                ['item1']
            ],
        ];
    }

    protected function getItemsForTranslatableValuesTest()
    {
        return [
            'items' => [
                'item1' => [
                    'options' => [
                        'translatable' => true,
                    ]
                ],
                'item2' => [
                    'options' => [
                        'translatable' => false,
                    ]
                ],
                'item3' => [
                    'options' => [
                    ]
                ],
                'item4' => [
                ],
            ]
        ];
    }

    public function getIndexedValuesProvider()
    {
        return [
            'no entity config'      => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                [],
            ],
            'entity config'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => $this->getItemsForIndexedValuesTest()
                ],
                [
                    'item1' => true
                ]
            ],
            'entity config (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => $this->getItemsForIndexedValuesTest()
                ],
                [
                    'item1' => true
                ]
            ],
            'field config'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => $this->getItemsForIndexedValuesTest()
                ],
                [
                    'item1' => true
                ]
            ],
            'field config (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => $this->getItemsForIndexedValuesTest()
                ],
                [
                    'item1' => true
                ]
            ],
        ];
    }

    protected function getItemsForIndexedValuesTest()
    {
        return [
            'items' => [
                'item1' => [
                    'options' => [
                        'indexed' => true,
                    ]
                ],
                'item2' => [
                    'options' => [
                        'indexed' => false,
                    ]
                ],
                'item3' => [
                    'options' => [
                    ]
                ],
                'item4' => [
                ],
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getFormItemsProvider()
    {
        return [
            'no entity config'                        => [
                PropertyConfigContainer::TYPE_ENTITY,
                null,
                [],
                [],
            ],
            'entity config'                           => [
                PropertyConfigContainer::TYPE_ENTITY,
                null,
                [
                    'entity' => $this->getItemsForFormItemsTest()
                ],
                [
                    'item1' => [
                        'form'    => [
                            'type' => 'SomeForm',
                        ],
                        'options' => [
                            'allowed_type' => ['string']
                        ],
                    ],
                    'item2' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ],
                    ],
                ]
            ],
            'entity config (by id)'                   => [
                new EntityConfigId('testScope', 'Test\Cls'),
                null,
                [
                    'entity' => $this->getItemsForFormItemsTest()
                ],
                [
                    'item1' => [
                        'form'    => [
                            'type' => 'SomeForm',
                        ],
                        'options' => [
                            'allowed_type' => ['string']
                        ],
                    ],
                    'item2' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ],
                    ],
                ]
            ],
            'field config (no field type)'            => [
                PropertyConfigContainer::TYPE_FIELD,
                null,
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                [
                    'item1' => [
                        'form'    => [
                            'type' => 'SomeForm',
                        ],
                        'options' => [
                            'allowed_type' => ['string']
                        ],
                    ],
                    'item2' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ],
                    ],
                ]
            ],
            'field config (no field type) (by id)'    => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                null,
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                [
                    'item1' => [
                        'form'    => [
                            'type' => 'SomeForm',
                        ],
                        'options' => [
                            'allowed_type' => ['string']
                        ],
                    ],
                    'item2' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ],
                    ],
                ]
            ],
            'field config'                            => [
                PropertyConfigContainer::TYPE_FIELD,
                'string',
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                [
                    'item1' => [
                        'form'    => [
                            'type' => 'SomeForm',
                        ],
                        'options' => [
                            'allowed_type' => ['string']
                        ],
                    ],
                    'item2' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ],
                    ],
                ]
            ],
            'field config (by id)'                    => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                'string',
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                [
                    'item1' => [
                        'form'    => [
                            'type' => 'SomeForm',
                        ],
                        'options' => [
                            'allowed_type' => ['string']
                        ],
                    ],
                    'item2' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ],
                    ],
                ]
            ],
            'field config (not allowed type)'         => [
                PropertyConfigContainer::TYPE_FIELD,
                'int',
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                [
                    'item2' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ],
                    ],
                ]
            ],
            'field config (not allowed type) (by id)' => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                'int',
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                [
                    'item2' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ],
                    ],
                ]
            ],
        ];
    }

    protected function getItemsForFormItemsTest()
    {
        return [
            'items' => [
                'item1' => [
                    'form'    => [
                        'type' => 'SomeForm'
                    ],
                    'options' => [
                        'allowed_type' => ['string']
                    ]
                ],
                'item2' => [
                    'form' => [
                        'type' => 'SomeForm'
                    ],
                ],
                'item3' => [
                    'form' => [
                    ],
                ],
                'item4' => [
                ],
            ]
        ];
    }

    public function hasFormProvider()
    {
        return [
            'no entity config'                        => [
                PropertyConfigContainer::TYPE_ENTITY,
                null,
                [],
                false,
            ],
            'entity config (no form)'                 => [
                PropertyConfigContainer::TYPE_ENTITY,
                'int',
                [
                    'entity' => [
                        'item1' => [
                            'form'    => [
                                'type' => 'SomeForm'
                            ],
                            'options' => [
                                'allowed_type' => ['string']
                            ]
                        ],
                        'item2' => [
                            'form' => [
                            ],
                        ],
                        'item3' => [
                        ],
                    ]
                ],
                false
            ],
            'entity config'                           => [
                PropertyConfigContainer::TYPE_ENTITY,
                null,
                [
                    'entity' => $this->getItemsForFormItemsTest()
                ],
                true
            ],
            'entity config (by id)'                   => [
                new EntityConfigId('testScope', 'Test\Cls'),
                null,
                [
                    'entity' => $this->getItemsForFormItemsTest()
                ],
                true
            ],
            'field config (no field type)'            => [
                PropertyConfigContainer::TYPE_FIELD,
                null,
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                true
            ],
            'field config (no field type) (by id)'    => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                null,
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                true
            ],
            'field config'                            => [
                PropertyConfigContainer::TYPE_FIELD,
                'string',
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                true
            ],
            'field config (by id)'                    => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                'string',
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                true
            ],
            'field config (not allowed type)'         => [
                PropertyConfigContainer::TYPE_FIELD,
                'int',
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                true
            ],
            'field config (not allowed type) (by id)' => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                'int',
                [
                    'field' => $this->getItemsForFormItemsTest()
                ],
                true
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getFormConfigProvider()
    {
        return [
            'no entity config'                     => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                [],
            ],
            'entity config'                        => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ]
                    ]
                ],
                ['type' => 'SomeForm']
            ],
            'entity config (no form type)'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'form' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (by id)'                => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ]
                    ]
                ],
                ['type' => 'SomeForm']
            ],
            'entity config (no form type) (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'form' => [
                        ]
                    ]
                ],
                []
            ],
            'field config'                         => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ]
                    ]
                ],
                ['type' => 'SomeForm']
            ],
            'field config (no form type)'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'form' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (by id)'                 => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'form' => [
                            'type' => 'SomeForm',
                        ]
                    ]
                ],
                ['type' => 'SomeForm']
            ],
            'field config (no form type) (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'form' => [
                        ]
                    ]
                ],
                []
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getFormBlockConfigProvider()
    {
        return [
            'no entity config'                        => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                null,
            ],
            'entity config'                           => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'form' => [
                            'block_config' => [
                                'test' => 'testVal',
                            ]
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (no block config)'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'form' => [
                        ]
                    ]
                ],
                null
            ],
            'entity config (by id)'                   => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'form' => [
                            'block_config' => [
                                'test' => 'testVal',
                            ]
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (no block config) (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'form' => [
                        ]
                    ]
                ],
                null
            ],
            'field config'                            => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'form' => [
                            'block_config' => [
                                'test' => 'testVal',
                            ]
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (no block config)'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'form' => [
                        ]
                    ]
                ],
                null
            ],
            'field config (by id)'                    => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'form' => [
                            'block_config' => [
                                'test' => 'testVal',
                            ]
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (no block config) (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'form' => [
                        ]
                    ]
                ],
                null
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getGridActionsProvider()
    {
        return [
            'no entity config'                           => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                [],
            ],
            'entity config'                              => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'grid_action' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (empty grid actions)'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'grid_action' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (no grid actions)'            => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                    ]
                ],
                []
            ],
            'entity config (by id)'                      => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'grid_action' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (empty grid actions) (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'grid_action' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (no grid actions) (by id)'    => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                    ]
                ],
                []
            ],
            'field config'                               => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'grid_action' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (empty grid actions)'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'grid_action' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (no grid actions)'             => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                    ]
                ],
                []
            ],
            'field config (by id)'                       => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'grid_action' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (empty grid actions) (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'grid_action' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (no grid actions) (by id)'     => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                    ]
                ],
                []
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getUpdateActionFilterProvider()
    {
        return [
            'no entity config'                     => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                null
            ],
            'entity config'                        => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'update_filter' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (empty filter)'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'update_filter' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (no filter)'            => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                    ]
                ],
                null
            ],
            'entity config (by id)'                => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'update_filter' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (empty filter) (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'update_filter' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (no filter) (by id)'    => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                    ]
                ],
                null
            ],
            'field config'                         => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'update_filter' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (empty filter)'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'update_filter' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (no filter)'             => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                    ]
                ],
                null
            ],
            'field config (by id)'                 => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'update_filter' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (empty filter) (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'update_filter' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (no filter) (by id)'     => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                    ]
                ],
                null
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getLayoutActionsProvider()
    {
        return [
            'no entity config'                      => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                []
            ],
            'entity config'                         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'layout_action' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (empty actions)'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'layout_action' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (no actions)'            => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                    ]
                ],
                []
            ],
            'entity config (by id)'                 => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'layout_action' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (empty actions) (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'layout_action' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (no actions) (by id)'    => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                    ]
                ],
                []
            ],
            'field config'                          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'layout_action' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (empty actions)'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'layout_action' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (no actions)'             => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                    ]
                ],
                []
            ],
            'field config (by id)'                  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'layout_action' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (empty actions) (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'layout_action' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (no actions) (by id)'     => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                    ]
                ],
                []
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getRequireJsModulesProvider()
    {
        return [
            'no entity config'                      => [
                PropertyConfigContainer::TYPE_ENTITY,
                [],
                []
            ],
            'entity config'                         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'require_js' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (empty modules)'         => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                        'require_js' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (no modules)'            => [
                PropertyConfigContainer::TYPE_ENTITY,
                [
                    'entity' => [
                    ]
                ],
                []
            ],
            'entity config (by id)'                 => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'require_js' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'entity config (empty modules) (by id)' => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                        'require_js' => [
                        ]
                    ]
                ],
                []
            ],
            'entity config (no modules) (by id)'    => [
                new EntityConfigId('testScope', 'Test\Cls'),
                [
                    'entity' => [
                    ]
                ],
                []
            ],
            'field config'                          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'require_js' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (empty modules)'          => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                        'require_js' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (no modules)'             => [
                PropertyConfigContainer::TYPE_FIELD,
                [
                    'field' => [
                    ]
                ],
                []
            ],
            'field config (by id)'                  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'require_js' => [
                            'test' => 'testVal',
                        ]
                    ]
                ],
                ['test' => 'testVal']
            ],
            'field config (empty modules) (by id)'  => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                        'require_js' => [
                        ]
                    ]
                ],
                []
            ],
            'field config (no modules) (by id)'     => [
                new FieldConfigId('testScope', 'Test\Cls', 'fieldName', 'int'),
                [
                    'field' => [
                    ]
                ],
                []
            ],
        ];
    }

    public function isSchemaUpdateRequiredProvider()
    {
        return [
            ['testAttr1', PropertyConfigContainer::TYPE_ENTITY, true],
            ['testAttr2', PropertyConfigContainer::TYPE_ENTITY, false],
            ['testAttr3', PropertyConfigContainer::TYPE_ENTITY, false],
            ['testAttr1', PropertyConfigContainer::TYPE_FIELD, true],
            ['testAttr2', PropertyConfigContainer::TYPE_FIELD, false],
            ['testAttr3', PropertyConfigContainer::TYPE_FIELD, false],
        ];
    }
}
