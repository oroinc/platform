<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\FilterHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class FilterHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider handleProvider
     * @param array $config
     * @param array $expected
     */
    public function testHandle(array $config, array $expected)
    {
        $filterHandler = new FilterHandler();

        $this->assertEquals($expected, $filterHandler->handle($config));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function handleProvider()
    {
        return [
            [
                'incoming' => [
                    'name' => 'test_workflow',
                    'label' => 'Will be removed',
                    WorkflowDefinition::CONFIG_SCOPES => [
                        ['scope1' => 'value1'],
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        [
                            'name' => 'test_attribute',
                            'label' => 'Test Attribute',
                            'type' => 'entity',
                            'entity_acl' => [
                                'delete' => false,
                            ],
                            'property_path' => 'entity.test_attribute',
                            'unknown_first' => 'first_value',
                            'unknown_second' => 'second_value',
                        ]
                    ],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        [
                            'name' => 'test_transition',
                            'label' => 'Test Transition',
                            'step_to' => 'test_step',
                            'transition_definition' => 'test_transition_definition',
                            'display_type' => 'type',
                            'destination_page' => 'dest',
                            'form_options' => [
                                'attribute_fields' => [
                                    'test_attribute' => [
                                        'options' => [
                                            'required' => true
                                        ],
                                    ]
                                ]
                            ],
                            'init_entities' => ['entity1'],
                            'init_routes' => ['route1'],
                            'init_datagrids' => ['datagrid1'],
                        ]
                    ],
                    WorkflowConfiguration::NODE_STEPS => [
                        [
                            'name' => 'step:starting_point',
                            'order' => -1,
                            '_is_start' => true,
                            'is_final' => false,
                            'allowed_transitions' => ['start_transition']
                        ],
                        [
                            'name' => 'first_step',
                            'label' => 'First Step',
                            'order' => 10,
                            '_is_start' => false,
                            'is_final' => true,
                            'unknown_first' => 'first_value',
                            'unknown_second' => 'second_value'
                        ],
                    ],
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                        [
                            'name' => 'trans_def_name',
                            'preactions' => [],
                            'preconditions' => [],
                            'unknown' => 'fv',
                            'actions' => []
                        ]
                    ],
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            'test_var' => [
                                'name' => 'var_name',
                                'type' => 'string',
                                'value' => 'test',
                                'options' => []
                            ],
                            'test_entity_var' => [
                                'name' => 'entity_var_name',
                                'type' => 'entity',
                                'value' => 1,
                                'property_path' => 'entity.test_variable',
                                'entity_acl' => [
                                    'delete' => false,
                                ],
                                'options' => [],
                                'unknownKey' => 'unkownValue',
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    'name' => 'test_workflow',
                    WorkflowDefinition::CONFIG_SCOPES => [
                        ['scope1' => 'value1'],
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        [
                            'name' => 'test_attribute',
                            'type' => 'entity',
                            'entity_acl' => [
                                'delete' => false,
                            ],
                            'property_path' => 'entity.test_attribute',
                        ]
                    ],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        [
                            'name' => 'test_transition',
                            'step_to' => 'test_step',
                            'transition_definition' => 'test_transition_definition',
                            'display_type' => 'type',
                            'destination_page' => 'dest',
                            'form_options' => [
                                'attribute_fields' => [
                                    'test_attribute' => [
                                        'options' => [
                                            'required' => true
                                        ],
                                    ]
                                ]
                            ],
                            'init_entities' => ['entity1'],
                            'init_routes' => ['route1'],
                            'init_datagrids' => ['datagrid1'],
                        ]
                    ],
                    WorkflowConfiguration::NODE_STEPS => [
                        [
                            'name' => 'step:starting_point',
                            'order' => -1,
                            '_is_start' => true,
                            'is_final' => '',
                            'allowed_transitions' => ['start_transition']
                        ],
                        [
                            'name' => 'first_step',
                            'order' => 10,
                            '_is_start' => false,
                            'is_final' => true
                        ],
                    ],
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                        [
                            'name' => 'trans_def_name',
                            'preactions' => [],
                            'preconditions' => [],
                            'actions' => []
                        ]
                    ],
                    WorkflowConfiguration::NODE_VARIABLE_DEFINITIONS => [
                        WorkflowConfiguration::NODE_VARIABLES => [
                            'test_var' => [
                                'name' => 'var_name',
                                'type' => 'string',
                                'value' => 'test',
                                'options' => []
                            ],
                            'test_entity_var' => [
                                'name' => 'entity_var_name',
                                'type' => 'entity',
                                'value' => 1,
                                'property_path' => 'entity.test_variable',
                                'entity_acl' => [
                                    'delete' => false,
                                ],
                                'options' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
