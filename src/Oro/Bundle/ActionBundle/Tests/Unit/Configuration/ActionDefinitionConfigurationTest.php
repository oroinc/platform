<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\ActionDefinitionConfiguration;

class ActionDefinitionConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionDefinitionConfiguration
     */
    protected $configuration;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->configuration = new ActionDefinitionConfiguration();
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider processValidConfigurationProvider
     */
    public function testProcessValidConfiguration(array $inputData, array $expectedData)
    {
        $this->assertEquals(
            $expectedData,
            $this->configuration->processConfiguration($inputData)
        );
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
            'min valid configuration' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label 1',
                    ],
                ],
                'expected' => [
                    'replace' => [],
                    'label' => 'Test Label 1',
                    'applications' => [],
                    'for_all_entities' => false,
                    'entities' => [],
                    'exclude_entities' => [],
                    'routes' => [],
                    'groups' => [],
                    'datagrids' => [],
                    'order' => 0,
                    'enabled' => true,
                    'prefunctions' => [],
                    'preconditions' => [],
                    'conditions' => [],
                    'form_init' => [],
                    'functions' => [],
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
                ],
            ],
            'full valid configuration' => [
                'input' => [
                    'action' => [
                        'replace' => 'test_replace',
                        'label' => 'Test Label 2',
                        'substitute_action' => 'test_action',
                        'applications' => ['app1', 'app2', 'app3'],
                        'for_all_entities' => true,
                        'entities' => ['Entity1', 'Entity2'],
                        'exclude_entities' => ['Entity3'],
                        'routes' => ['route_1', 'route_2'],
                        'groups' => ['group_1', 'group_2'],
                        'datagrids' => ['datagrid_1', 'datagrid_2'],
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
                        'prefunctions' => [
                            '@create_date' => [],
                        ],
                        'preconditions' => [
                            '@equal' => ['$field1', 'value1'],
                        ],
                        'form_init' => [
                            '@assign_value' => ['$field1', 'value2'],
                        ],
                        'functions' => [
                            '@call_method' => [],
                        ],
                        'attributes' => [
                            'test_attribute' => [
                                'type' => 'string',
                                'label' => 'Test Attribute Label'
                            ]
                        ]
                    ],
                ],
                'expected' => [
                    'replace' => ['test_replace'],
                    'label' => 'Test Label 2',
                    'substitute_action' => 'test_action',
                    'applications' => ['app1', 'app2', 'app3'],
                    'for_all_entities' => true,
                    'entities' => ['Entity1', 'Entity2'],
                    'exclude_entities' => ['Entity3'],
                    'routes' => ['route_1', 'route_2'],
                    'groups' => ['group_1', 'group_2'],
                    'datagrids' => ['datagrid_1', 'datagrid_2'],
                    'order' => 15,
                    'enabled' => false,
                    'prefunctions' => [
                        '@create_date' => [],
                    ],
                    'preconditions' => [
                        '@equal' => ['$field1', 'value1'],
                    ],
                    'conditions' => [],
                    'form_init' => [
                        '@assign_value' => ['$field1', 'value2'],
                    ],
                    'functions' => [
                        '@call_method' => [],
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
     */
    protected function invalidConfigurationProvider()
    {
        return [
            'incorrect root' => [
                'input' => [
                    'action' => 'not array value',
                ],
                'message' => 'Invalid type for path "action". Expected array, but got string'
            ],
            'empty action[label]' => [
                'input' => [
                    'action' => [],
                ],
                'message' => 'The child node "label" at path "action" must be configured'
            ],
            'incorrect action[substitute_action]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'substitute_action' => ['array', 'value']
                    ]
                ],
                'message' => 'Invalid type for path "action.substitute_action". Expected scalar, but got array'
            ],
            'incorrect action[application]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.applications". Expected array, but got string'
            ],
            'incorrect action[entities]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.entities". Expected array, but got string'
            ],
            'incorrect action[routes]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.routes". Expected array, but got string'
            ],
            'incorrect action[groups]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'groups' => 'not array route'
                    ]
                ],
                'message' => 'Invalid type for path "action.groups". Expected array, but got string'
            ],
            'incorrect action[order]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 'not integer value',
                    ],
                ],
                'message' => 'Invalid type for path "action.order". Expected int, but got string'
            ],
            'incorrect action[enabled]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => 'not bool value',
                    ],
                ],
                'message' => 'Invalid type for path "action.enabled". Expected boolean, but got string'
            ],
            'incorrect action[frontend_options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => [],
                        'routes' => [],
                        'order' => 10,
                        'enabled' => true,
                        'frontend_options' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.frontend_options". Expected array, but got string'
            ],
        ];
    }

    /**
     * @return array
     */
    protected function invalidAttributeProvider()
    {
        return [
            'incorrect action[attribute]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes". Expected array, but got string'
            ],
            'incorrect action[attribute][type]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'test' => [
                                'type' => []
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes.test.type". Expected scalar, but got array'
            ],
            'incorrect action[attribute][label]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'test' => [
                                'type' => 'type',
                                'label' => []
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes.test.label". Expected scalar, but got array'
            ],
            'incorrect action[attribute][options]' => [
                'input' => [
                    'action' => [
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
                'message' => 'Invalid type for path "action.attributes.test.options". Expected array, but got integer'
            ],
        ];
    }

    /**
     * @return array
     */
    protected function invalidFormOptionsProvider()
    {
        return [
            'incorrect action[form_options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'form_options' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.form_options". Expected array, but got string'
            ],
            'incorrect action[form_options][attribute_fields][options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'form_options' => [
                            'attribute_fields' => [
                                'options' => 'not array value',
                            ],
                        ]
                    ],
                ],
                'message' =>
                    'Invalid type for path "action.form_options.attribute_fields.options". ' .
                    'Expected array, but got string'
            ],
            'incorrect action[form_options][attribute_fields]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'form_options' => [
                            'attribute_fields' => 'not array value',
                        ]
                    ],
                ],
                'message' =>
                    'Invalid type for path "action.form_options.attribute_fields". ' .
                    'Expected array, but got string'
            ],
            'incorrect action[form_options][attribute_default_values]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'form_options' => [
                            'attribute_default_values' => 'not array value',
                        ]
                    ],
                ],
                'message' =>
                    'Invalid type for path "action.form_options.attribute_default_values". ' .
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
            'incorrect action[attributes]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes". Expected array, but got string'
            ],
            'incorrect action[attributes][0]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'bad_attribute'
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes.0". Expected array, but got string'
            ],
            'incorrect action[attributes][attribute1]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute1' => 'not array value',
                        ],
                    ],
                ],
                'message' => 'Invalid type for path "action.attributes.attribute1". Expected array, but got string'
            ],
            'empty action[attributes][attribute2][type] and action[attributes][attribute2][property_path]' => [
                'input' => [
                    'action' => [
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
            'invalid action[attributes][attribute3][type]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'attributes' => [
                            'attribute3' => [
                                'type' => 'type1',
                            ],
                        ],
                    ],
                ],
                'message' => 'The value "type1" is not allowed for path "action.attributes.attribute3.type"'
            ],
            'empty action[attributes][attribute4][label] and  and action[attributes][attribute2][property_path]' => [
                'input' => [
                    'action' => [
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
            'incorrect action[attributes][attribute5][entity_acl]' => [
                'input' => [
                    'action' => [
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
                    'Invalid type for path "action.attributes.attribute5.entity_acl". ' .
                    'Expected array, but got string'
            ],
            'used entity_acl & !entity type action[attributes][attribute6]' => [
                'input' => [
                    'action' => [
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
                    'Invalid configuration for path "action.attributes.attribute6": ' .
                    'Attribute "Attribute 6 Label" with type "int" can\'t have entity ACL'
            ],
            'empty action[attributes][attribute7][options][class] with object' => [
                'input' => [
                    'action' => [
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
                    'Invalid configuration for path "action.attributes.attribute7": ' .
                    'Option "class" is required for "object" type'
            ],
            'empty action[attributes][attribute8][options][class] with entity' => [
                'input' => [
                    'action' => [
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
                    'Invalid configuration for path "action.attributes.attribute8": ' .
                    'Option "class" is required for "entity" type'
            ],
            'excess option action[attributes][attribute9][options][class] with !entity|object' => [
                'input' => [
                    'action' => [
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
                    'Invalid configuration for path "action.attributes.attribute9": ' .
                    'Option "class" cannot be used for "bool" type'
            ],
        ];
    }

    /**
     * @return array
     */
    protected function invaliddatagridOptionsProvider()
    {
        return [
            'incorrect action[datagrid_options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'datagrid_options' => '',
                    ],
                ],
                'message' => 'Invalid type for path "action.datagrid_options". Expected array, but got string'
            ],
            'specified both options of action[datagrid_options]' => [
                'input' => [
                    'action' => [
                        'label' => 'Test Label',
                        'datagrid_options' => [
                            'mass_action_provider' => 'test',
                            'mass_action' => [
                                'data' => 'value'
                            ]
                        ],
                    ],
                ],
                'message' => 'Invalid configuration for path "action.datagrid_options": ' .
                    'Must be specified only one parameter "mass_action_provider" or "mass_action"'
            ],
        ];
    }
}
