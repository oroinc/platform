<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\Configuration;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    /**
     * @dataProvider processValidConfigurationForOperationsProvider
     */
    public function testProcessValidConfigurationForOperations(array $inputData, array $expectedData)
    {
        $result = $this->processConfiguration(['actions' => ['operations' => $inputData]]);
        $this->assertEquals($expectedData, $result['operations']);
    }

    /**
     * @dataProvider processInvalidConfigurationForOperationsProvider
     */
    public function testProcessInvalidConfigurationForOperations(array $inputData, $expectedExceptionMessage)
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->processConfiguration(['actions' => ['operations' => $inputData]]);
    }

    /**
     * @dataProvider processValidConfigurationForActionGroupsProvider
     */
    public function testProcessValidConfigurationForActionGroups(array $inputData, array $expectedData)
    {
        $result = $this->processConfiguration(['actions' => ['action_groups' => $inputData]]);
        $this->assertEquals($expectedData, $result['action_groups']);
    }

    /**
     * @dataProvider processInvalidConfigurationForActionGroupsProvider
     */
    public function testProcessInvalidConfigurationForActionGroups(array $inputData, $expectedExceptionMessage)
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->processConfiguration(['actions' => ['action_groups' => $inputData]]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processValidConfigurationForOperationsProvider(): array
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
                        'page_reload' => true,
                        OperationDefinition::PREACTIONS => [],
                        OperationDefinition::PRECONDITIONS => [],
                        OperationDefinition::CONDITIONS => [],
                        OperationDefinition::ACTIONS => [],
                        OperationDefinition::FORM_INIT => [],
                        'attributes' => [],
                        'frontend_options' => [
                            'options' => [],
                            'show_dialog' => true,
                            'title_parameters' => []
                        ],
                        'button_options' => [
                            'page_component_options' => [],
                            'data' => []
                        ],
                        'datagrid_options' => [
                            'mass_action' => [],
                            'data' => [],
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
                        'page_reload' => false,
                        'frontend_options' => [
                            'template' => 'template',
                            'title' => 'dialog title',
                            'title_parameters' => ['param' => 'value'],
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
                            ],
                            'data' => [
                                'key1' => 'value1'
                            ],
                            'aria_label' => 'test_aria_label',
                        ],
                        'form_options' => [
                            'validation_groups' => ['Default', 'Optional'],
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
                        'page_reload' => false,
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
                            'title_parameters' => ['param' => 'value'],
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
                            'validation_groups' => ['Default', 'Optional'],
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
                            ],
                            'data' => [
                                'key1' => 'value1'
                            ],
                            'aria_label' => 'test_aria_label',
                        ],
                    ]
                ],
            ],
        ];
    }

    public function processInvalidConfigurationForOperationsProvider(): array
    {
        return array_merge(
            $this->invalidConfigurationForOperationsProvider(),
            $this->invalidAttributeForOperationsProvider(),
            $this->invalidFormOptionsForOperationsProvider(),
            $this->invalidAttributesForOperationsProvider(),
            $this->invalidDatagridOptionsForOperationsProvider()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function invalidConfigurationForOperationsProvider(): array
    {
        return [
            'incorrect root' => [
                'input' => [
                    'oper1' => 'not array value'
                ],
                'message' => 'Invalid type for path "actions.operations.oper1". Expected "array", but got "string"'
            ],
            'incorrect array' => [
                'input' => [
                    'oper1' => []
                ],
                'message' => 'The child config "label" under "actions.operations.oper1" must be configured'
            ],
            'incorrect operation[substitute_operation]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'substitute_operation' => ['array', 'value']
                    ]
                ],
                'message' => 'Invalid type for path "actions.operations.oper1.substitute_operation". '
                    . 'Expected "scalar", but got "array"'
            ],
            'incorrect action[application]' => [
                'input' => [
                    'oper1' => []
                ],
                'message' => 'The child config "label" under "actions.operations.oper1" must be configured'
            ],
            'incorrect operation[application]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => 'not array value',
                    ]
                ],
                'message' => 'Invalid type for path "actions.operations.oper1.applications".'
                    . ' Expected "array", but got "string"'
            ],
            'incorrect operation[entities]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'applications' => [],
                        'entities' => 'not array value',
                    ]
                ],
                'message' => 'Invalid type for path "actions.operations.oper1.entities".'
                    . ' Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.routes".'
                    . ' Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.groups".'
                    . ' Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.order". Expected "int", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.enabled".'
                    . ' Expected "bool", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.frontend_options".'
                    . ' Expected "array", but got "string"'
            ],
        ];
    }

    private function invalidAttributeForOperationsProvider(): array
    {
        return [
            'incorrect operation[attribute]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "actions.operations.oper1.attributes".'
                    . ' Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.attributes.test.type". '
                    . 'Expected "scalar", but got "array"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.attributes.test.label". '
                    . 'Expected "scalar", but got "array"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.attributes.test.options". '
                    . 'Expected "array", but got "int"'
            ],
        ];
    }

    private function invalidFormOptionsForOperationsProvider(): array
    {
        return [
            'incorrect operation[form_options]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'form_options' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "actions.operations.oper1.form_options".'
                    . ' Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.form_options.attribute_fields.options". '
                    . 'Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.form_options.attribute_fields". '
                    . 'Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.form_options.attribute_default_values". '
                    . 'Expected "array", but got "string"'
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function invalidAttributesForOperationsProvider(): array
    {
        return [
            'incorrect operation[attributes]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'attributes' => 'not array value',
                    ],
                ],
                'message' => 'Invalid type for path "actions.operations.oper1.attributes".'
                    . ' Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.attributes.0".'
                    . ' Expected "array", but got "string"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.attributes.attribute1". '
                    . 'Expected "array", but got "string"'
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
                'message' => 'The value "type1" is not allowed'
                    . ' for path "actions.operations.oper1.attributes.attribute3.type"'
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
                'message' => 'Invalid type for path "actions.operations.oper1.attributes.attribute5.entity_acl". '
                    . 'Expected "array", but got "string"'
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
                'message' => 'Invalid configuration for path "actions.operations.oper1.attributes.attribute6": '
                    . 'Attribute "Attribute 6 Label" with type "int" can\'t have entity ACL'
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
                'message' => 'Invalid configuration for path "actions.operations.oper1.attributes.attribute7": '
                    . 'Option "class" is required for "object" type'
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
                'message' => 'Invalid configuration for path "actions.operations.oper1.attributes.attribute8": '
                    . 'Option "class" is required for "entity" type'
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
                'message' => 'Invalid configuration for path "actions.operations.oper1.attributes.attribute9": '
                    . 'Option "class" cannot be used for "bool" type'
            ],
        ];
    }

    private function invalidDatagridOptionsForOperationsProvider(): array
    {
        return [
            'incorrect operation[datagrid_options]' => [
                'input' => [
                    'oper1' => [
                        'label' => 'Test Label',
                        'datagrid_options' => '',
                    ],
                ],
                'message' => 'Invalid type for path "actions.operations.oper1.datagrid_options".'
                    . ' Expected "array", but got "string"'
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
                'message' => 'Invalid configuration for path "actions.operations.oper1.datagrid_options": '
                    . 'Must be specified only one parameter "mass_action_provider" or "mass_action"'
            ],
        ];
    }

    public function processValidConfigurationForActionGroupsProvider(): array
    {
        return [
            'empty configuration' => [
                'config' => [],
                'expected' => []
            ],
            'min valid configuration' => [
                'config' => [
                    'test' => []
                ],
                'expected' => [
                    'test' => [
                        'parameters' => [],
                        'conditions' => [],
                        'actions' => []
                    ]
                ]
            ],
            'max valid configuration' => [
                'config' => [
                    'test' => [
                        'acl_resource' => ['EDIT', new \stdClass()],
                        'parameters' => [
                            'arg1' => [
                                'type' => 'string',
                                'message' => 'Exception message',
                                'default' => 'test string'
                            ],
                            'arg2' => []
                        ],
                        'conditions' => [
                            '@equal' => ['a1', 'b1']
                        ],
                        'actions' => [
                            '@assign_value' => ['$field1', 'value2']
                        ]
                    ]
                ],
                'expected' => [
                    'test' => [
                        'acl_resource' => ['EDIT', new \stdClass()],
                        'parameters' => [
                            'arg1' => [
                                'type' => 'string',
                                'message' => 'Exception message',
                                'default' => 'test string'
                            ],
                            'arg2' => []
                        ],
                        'conditions' => [
                            '@equal' => ['a1', 'b1']
                        ],
                        'actions' => [
                            '@assign_value' => ['$field1', 'value2']
                        ]
                    ]
                ]
            ]
        ];
    }

    public function processInvalidConfigurationForActionGroupsProvider(): array
    {
        return [
            'incorrect root' => [
                'config' => [
                    'group1' => 'not array value'
                ],
                'message' => 'Invalid type for path "actions.action_groups.group1". Expected "array", but got "string"'
            ],
            'incorrect action_groups[parameters]' => [
                'input' => [
                    'group1' => [
                        'parameters' => 'not array value'
                    ]
                ],
                'message' => 'Invalid type for path "actions.action_groups.group1.parameters".'
                    . ' Expected "array", but got "string"'
            ],
            'incorrect array action_groups[parameters]' => [
                'input' => [
                    'group1' => [
                        'parameters' => ['not array value']
                    ]
                ],
                'message' => 'Invalid type for path "actions.action_groups.group1.parameters.0".'
                    . ' Expected "array", but got "string"'
            ],
            'incorrect action_groups[parameters][type]' => [
                'input' => [
                    'group1' => [
                        'parameters' => [
                            'arg1' => [
                                'type' => []
                            ]
                        ]
                    ]
                ],
                'message' => 'Invalid type for path "actions.action_groups.group1.parameters.arg1.type". '
                    . 'Expected "scalar", but got "array"'
            ],
            'incorrect action_groups[parameters][message]' => [
                'input' => [
                    'group1' => [
                        'parameters' => [
                            'arg1' => [
                                'message' => []
                            ]
                        ]
                    ]
                ],
                'message' => 'Invalid type for path "actions.action_groups.group1.parameters.arg1.message". '
                    . 'Expected "scalar", but got "array"'
            ],
            'incorrect action_groups[conditions]' => [
                'input' => [
                    'group1' => [
                        'conditions' => 'not array value'
                    ]
                ],
                'message' => 'Invalid type for path "actions.action_groups.group1.conditions".'
                    . ' Expected "array", but got "string"'
            ],
            'incorrect action_groups[actions]' => [
                'input' => [
                    'group1' => [
                        'actions' => 'not array value'
                    ]
                ],
                'message' => 'Invalid type for path "actions.action_groups.group1.actions".'
                    . ' Expected "array", but got "string"'
            ],
        ];
    }
}
