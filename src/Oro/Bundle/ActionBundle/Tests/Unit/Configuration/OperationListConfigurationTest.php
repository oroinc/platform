<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\OperationListConfiguration;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;

class OperationListConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OperationListConfiguration
     */
    protected $configuration;

    public function setUp()
    {
        $this->configuration = new OperationListConfiguration();
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider processValidConfigurationProvider
     */
    public function testProcessValidConfiguration(array $inputData, array $expectedData)
    {
        $this->assertEquals($expectedData, $this->configuration->processConfiguration($inputData));
    }

    /**
     * @dataProvider processInvalidConfigurationProvider
     *
     * @param array $inputData
     * @param string $expectedExceptionMessage
     */
    public function testProcessInvalidConfiguration(array $inputData, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            $expectedExceptionMessage
        );

        $this->configuration->processConfiguration($inputData);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processValidConfigurationProvider()
    {
        return [
            'empty configuration' => [
                'input' => [],
                'expected' => []
            ],
            'min valid configuration' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label 1',
                    ],
                ],
                'expected' => [
                    'oper1' => [
                        'replace' => [],
                        'label' => 'Test Label 1',
                        'applications' => [],
                        'for_all_entities' => false,
                        'entities' => [],
                        'exclude_entities' => [],
                        'routes' => [],
                        'groups' => [],
                        'for_all_datagrids' => false,
                        'datagrids' => [],
                        'exclude_datagrids' => [],
                        'order' => 0,
                        'enabled' => true,
                        OperationDefinition::PREACTIONS => [],
                        OperationDefinition::PRECONDITIONS => [],
                        OperationDefinition::CONDITIONS => [],
                        OperationDefinition::ACTIONS => [],
                        OperationDefinition::FORM_INIT => [],
                        'attributes' => [],
                        'frontend_options' => [
                            'options' => [],
                            'show_dialog' => true
                        ],
                        'button_options' => [
                            'page_component_options' => [],
                            'data' => []
                        ],
                        'datagrid_options' => [
                            'mass_action' => []
                        ]
                    ]
                ]
            ],
            'full valid configuration' => [
                'input' => [
                    'oper1' => [
                        'replace' => 'test_replace',
                        'label' => 'Test Label 2',
                        'substitute_operation' => 'test_action',
                        'applications' => ['app1', 'app2', 'app3'],
                        'for_all_entities' => true,
                        'entities' => ['Entity1', 'Entity2'],
                        'exclude_entities' => ['Entity3'],
                        'routes' => ['route_1', 'route_2'],
                        'groups' => ['group_1', 'group_2'],
                        'for_all_datagrids' => true,
                        'datagrids' => ['datagrid_1', 'datagrid_2'],
                        'exclude_datagrids' => ['datagrid_3'],
                        'order' => 15,
                        'enabled' => false,
                        'frontend_options' => [
                            'template' => 'template',
                            'title' => 'dialog title',
                            'options' => ['width' => 400],
                            'confirmation' => 'Confirmation message',
                            'show_dialog' => false
                        ],
                        'button_options' => [
                            'icon' => 'icon',
                            'class' => 'class',
                            'group' => 'group label',
                            'template' => 'template',
                            'page_component_module' => 'testbundle/app/component',
                            'page_component_options' => [
                                'param' => 'value'
                            ],
                            'data' => [
                                'attribute' => 'attrValue'
                            ]
                        ],
                        'datagrid_options' => [
                            'mass_action' => [
                                'icon' => 'test'
                            ]
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute_1' => [
                                    'form_type' => 'test type',
                                    'options' => [
                                        'class' => 'testClass',
                                    ]
                                ]
                            ],
                            'attribute_default_values' => [
                                'attribute_1' => 'value 1',
                            ]
                        ],
                        OperationDefinition::PREACTIONS => [
                            '@create_date' => [],
                        ],
                        OperationDefinition::PRECONDITIONS => [
                            '@equal' => ['$field1', 'value1'],
                        ],
                        OperationDefinition::CONDITIONS => [
                            '@equal' => ['$field2', 'value2'],
                        ],
                        OperationDefinition::ACTIONS => [
                            '@action1' => [],
                        ],
                        OperationDefinition::FORM_INIT => [
                            '@assign_value' => ['$field1', 'value2'],
                        ],
                        'attributes' => [
                            'test_attribute' => [
                                'type' => 'string',
                                'label' => 'Test Attribute Label'
                            ]
                        ],
                    ],
                ],
                'expected' => [
                    'oper1' => [
                        'replace' => ['test_replace'],
                        'label' => 'Test Label 2',
                        'substitute_operation' => 'test_action',
                        'applications' => ['app1', 'app2', 'app3'],
                        'for_all_entities' => true,
                        'entities' => ['Entity1', 'Entity2'],
                        'exclude_entities' => ['Entity3'],
                        'routes' => ['route_1', 'route_2'],
                        'groups' => ['group_1', 'group_2'],
                        'for_all_datagrids' => true,
                        'datagrids' => ['datagrid_1', 'datagrid_2'],
                        'exclude_datagrids' => ['datagrid_3'],
                        'order' => 15,
                        'enabled' => false,
                        OperationDefinition::PREACTIONS => [
                            '@create_date' => [],
                        ],
                        OperationDefinition::PRECONDITIONS => [
                            '@equal' => ['$field1', 'value1'],
                        ],
                        OperationDefinition::CONDITIONS => [
                            '@equal' => ['$field2', 'value2'],
                        ],
                        OperationDefinition::ACTIONS => [
                            '@action1' => [],
                        ],
                        OperationDefinition::FORM_INIT => [
                            '@assign_value' => ['$field1', 'value2'],
                        ],
                        'attributes' => [
                            'test_attribute' => [
                                'type' => 'string',
                                'label' => 'Test Attribute Label',
                                'property_path' => null,
                                'options' => []
                            ]
                        ],
                        'frontend_options' => [
                            'template' => 'template',
                            'title' => 'dialog title',
                            'options' => ['width' => 400],
                            'confirmation' => [
                                'message' => 'Confirmation message',
                            ],
                            'show_dialog' => false
                        ],
                        'button_options' => [
                            'icon' => 'icon',
                            'class' => 'class',
                            'group' => 'group label',
                            'template' => 'template',
                            'page_component_module' => 'testbundle/app/component',
                            'page_component_options' => [
                                'param' => 'value'
                            ],
                            'data' => [
                                'attribute' => 'attrValue'
                            ]
                        ],
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute_1' => [
                                    'form_type' => 'test type',
                                    'options' => [
                                        'class' => 'testClass',
                                    ],
                                ]
                            ],
                            'attribute_default_values' => [
                                'attribute_1' => 'value 1',
                            ]
                        ],
                        'datagrid_options' => [
                            'mass_action' => [
                                'icon' => 'test'
                            ]
                        ],
                    ]
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function processInvalidConfigurationProvider()
    {
        return array_merge(
            $this->invalidConfigurationProvider(),
            $this->invalidAttributeProvider(),
            $this->invalidFormOptionsProvider(),
            $this->invalidAttributesProvider(),
            $this->invalidDatagridOptionsProvider()
        );
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function invalidConfigurationProvider()
    {
        return [
            'incorrect root' => [
                'input' => [
                    'oper1' => 'not array value'
                ],
                'message' => 'Invalid type for path "operations.oper1". Expected array, but got string'
            ],
            'incorrect array' => [
                'input' => [
                    'oper1' => []
                ],
                'message' => 'The child node "label" at path "operations.oper1" must be configured'
            ],
            'incorrect operation[substitute_operation]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'substitute_operation' => ['array', 'value']
                    ]
                ],
                'message' => 'Invalid type for path "operations.oper1.substitute_operation". ' .
                    'Expected scalar, but got array'
            ],
            'incorrect action[application]' => [
                'input' => [
                    'oper1' => []
                ],
                'message' => 'The child node "label" at path "operations.oper1" must be configured'
            ],
            'incorrect operation[application]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => 'not array value',
                    ]
                ],
                'message' => 'Invalid type for path "operations.oper1.applications". Expected array, but got string'
            ],
            'incorrect operation[entities]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => 'not array value',
                    ]
                ],
                'message' => 'Invalid type for path "operations.oper1.entities". Expected array, but got string'
            ],
            'incorrect operation[routes]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => 'not array value',
                    ]
                ],
                'message' => 'Invalid type for path "operations.oper1.routes". Expected array, but got string'
            ],
            'incorrect operation[groups]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'groups' => 'not array route'
                    ]
                ],
                'message' => 'Invalid type for path "operations.oper1.groups". Expected array, but got string'
            ],
            'incorrect action[order]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 'not integer value',
                    ]
                ],
                'message' => 'Invalid type for path "operations.oper1.order". Expected int, but got string'
            ],
            'incorrect operation[enabled]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => 'not bool value',
                    ]
                ],
                'message' => 'Invalid type for path "operations.oper1.enabled". Expected boolean, but got string'
            ],
            'incorrect operation[frontend_options]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => true,
                        'frontend_options' => 'not array value',
                    ]
                ],
                'message' => 'Invalid type for path "operations.oper1.frontend_options". Expected array, but got string'
            ],
        ];
    }

    /**
     * @return array
     */
    protected function invalidAttributeProvider()
    {
        return [
            'incorrect operation[attribute]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.attributes". Expected array, but got string'
            ],
            'incorrect operation[attribute][type]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'test' => [
                                'type' => []
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.attributes.test.type". ' .
                    'Expected scalar, but got array'
            ],
            'incorrect operation[attribute][label]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'test' => [
                                'type' => 'type',
                                'label' => []
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.attributes.test.label". ' .
                    'Expected scalar, but got array'
            ],
            'incorrect operation[attribute][options]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'test' => [
                                'type' => 'type',
                                'label' => 'label',
                                'options' => 1
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.attributes.test.options". ' .
                    'Expected array, but got integer'
            ],
        ];
    }

    /**
     * @return array
     */
    protected function invalidFormOptionsProvider()
    {
        return [
            'incorrect operation[form_options]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'form_options' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.form_options". Expected array, but got string'
            ],
            'incorrect operation[form_options][attribute_fields][options]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'form_options' => [
                            'attribute_fields' => [
                                'options' => 'not array value',
                            ],
                        ]
                    ],
                ],
                'message' =>
                    'Invalid type for path "operations.oper1.form_options.attribute_fields.options". ' .
                    'Expected array, but got string'
            ],
            'incorrect operation[form_options][attribute_fields]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'form_options' => [
                            'attribute_fields' => 'not array value',
                        ]
                    ],
                ],
                'message' =>
                    'Invalid type for path "operations.oper1.form_options.attribute_fields". ' .
                    'Expected array, but got string'
            ],
            'incorrect operation[form_options][attribute_default_values]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'form_options' => [
                            'attribute_default_values' => 'not array value',
                        ]
                    ],
                ],
                'message' =>
                    'Invalid type for path "operations.oper1.form_options.attribute_default_values". ' .
                    'Expected array, but got string'
            ],
        ];
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function invalidAttributesProvider()
    {
        return [
            'incorrect operation[attributes]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.attributes". Expected array, but got string'
            ],
            'incorrect operation[attributes][0]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'bad_attribute'
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.attributes.0". Expected array, but got string'
            ],
            'incorrect operation[attributes][attribute1]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute1' => 'not array value',
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.attributes.attribute1". ' .
                    'Expected array, but got string'
            ],
            'empty operation[attributes][attribute2][type] and operation[attributes][attribute2][property_path]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute2' => [
                                'label' => 'label1',
                            ],
                        ],
                    ],
                ],
                'message' => 'Option "type" or "property_path" is required'
            ],
            'invalid operation[attributes][attribute3][type]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute3' => [
                                'type' => 'type1',
                            ],
                        ],
                    ],
                ],
                'message' => 'The value "type1" is not allowed for path "operations.oper1.attributes.attribute3.type"'
            ],
            'empty operation[attributes][attribute4][label] and operation[attributes][attribute2][property_path]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute4' => [
                                'type' => 'bool',
                            ],
                        ],
                    ],
                ],
                'message' => 'Option "label" or "property_path" is required'
            ],
            'incorrect operation[attributes][attribute5][entity_acl]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute5' => [
                                'type' => 'int',
                                'label' => 'Attribute 5 Label',
                                'entity_acl' => 'not array value',
                            ],
                        ],
                    ],
                ],
                'message' =>
                    'Invalid type for path "operations.oper1.attributes.attribute5.entity_acl". ' .
                    'Expected array, but got string'
            ],
            'used entity_acl & !entity type operation[attributes][attribute6]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute6' => [
                                'type' => 'int',
                                'label' => 'Attribute 6 Label',
                                'entity_acl' => [],
                            ],
                        ],
                    ],
                ],
                'message' =>
                    'Invalid configuration for path "operations.oper1.attributes.attribute6": ' .
                    'Attribute "Attribute 6 Label" with type "int" can\'t have entity ACL'
            ],
            'empty operation[attributes][attribute7][options][class] with object' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute7' => [
                                'type' => 'object',
                                'label' => 'Attribute 7 Label',
                            ],
                        ],
                    ],
                ],
                'message' =>
                    'Invalid configuration for path "operations.oper1.attributes.attribute7": ' .
                    'Option "class" is required for "object" type'
            ],
            'empty operation[attributes][attribute8][options][class] with entity' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute8' => [
                                'type' => 'entity',
                                'label' => 'Attribute 8 Label',
                            ],
                        ],
                    ],
                ],
                'message' =>
                    'Invalid configuration for path "operations.oper1.attributes.attribute8": ' .
                    'Option "class" is required for "entity" type'
            ],
            'excess option operation[attributes][attribute9][options][class] with !entity|object' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute9' => [
                                'type' => 'bool',
                                'label' => 'Attribute 9 Label',
                                'options' => [
                                    'class' => 'TestClass9',
                                ],
                            ],
                        ],
                    ],
                ],
                'message' =>
                    'Invalid configuration for path "operations.oper1.attributes.attribute9": ' .
                    'Option "class" cannot be used for "bool" type'
            ],
        ];
    }

    /**
     * @return array
     */
    protected function invalidDatagridOptionsProvider()
    {
        return [
            'incorrect operation[datagrid_options]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'datagrid_options' => '',
                    ],
                ],
                'message' => 'Invalid type for path "operations.oper1.datagrid_options". Expected array, but got string'
            ],
            'specified both options of operation[datagrid_options]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'datagrid_options' => [
                            'mass_action_provider' => 'test',
                            'mass_action' => [
                                'data' => 'value'
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid configuration for path "operations.oper1.datagrid_options": ' .
                    'Must be specified only one parameter "mass_action_provider" or "mass_action"'
            ],
        ];
    }
}
