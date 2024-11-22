<?php

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

return [
    'first_workflow' => [
        'entity' => 'First\Entity',
        'is_system' => true,
        'start_step' => 'first_step',
        'entity_attribute' => 'my_entity',
        'steps_display_ordered' => true,
        'priority' => 1,
        'defaults' => [
            'active' => true,
        ],
        'metadata' => [],
        'scopes' => [
            [
                'scope1' => 'value1',
                'scope2' => 'value2',
            ],
            [
                'scope1' => 'value3',
            ],
        ],
        'datagrids' => ['datagrid1', 'datagrid2'],
        'steps' => [
            'first_step' => [
                'order' => 1,
                'is_final' => true,
                'entity_acl' => [
                    'first_attribute' => ['update' => false, 'delete' => true]
                ],
                'allowed_transitions' => ['first_transition'],
                '_is_start' => '',
                'position' => []
            ]
        ],
        'attributes' => [
            'first_attribute' => [
                'type' => 'object',
                'options' => [
                    'class' => 'DateTime'
                ],
                'property_path' => null,
                'default' => null,
            ],
            'second_attribute' => [
                'type' => 'entity',
                'entity_acl' => [
                    'update' => true,
                    'delete' => false,
                ],
                'property_path' => 'first_attribute.test',
                'options' => [
                    'class' => 'DateTime',
                ],
                'default' => null,
            ]
        ],
        'variable_definitions' => [
            'variables' => [
                'var1' => [
                    'type' => 'string',
                    'value' => 'Var1Value',
                    'options' => [],
                    'property_path' => null,
                ],
                'var2' => [
                    'type' => 'array',
                    'value' => [1,2,3,4,5],
                    'options' => [],
                    'property_path' => null,
                ],
                'var3' => [
                    'type' => 'string',
                    'value' => null,
                    'options' => [],
                    'property_path' => null,
                ],
                'first_object' => [
                    'type' => 'object',
                    'property_path' => null,
                    'value' => '2017-03-15 00:00:00',
                    'options' => [
                        'class' => 'DateTime'
                    ]
                ],
                'var_entity' => [
                    'type' => 'entity',
                    'property_path' => null,
                    'entity_acl' => [
                        'update' => true,
                        'delete' => false
                    ],
                    'options' => [
                        'class' => 'Oro\Bundle\UserBundle\Entity\User'
                    ],
                    'value' => 1
                ]
            ]
        ],
        'disable_operations' => [
            'operation1' => ['entity1', 'entity2']
        ],
        'transitions' => [
            'first_transition' => [
                'step_to' => 'first_step',
                'conditional_steps_to' => [],
                'is_start' => true,
                'is_hidden' => true,
                'is_unavailable_hidden' => true,
                'acl_resource' => 'some_acl',
                'acl_message' => 'Test ACL message',
                'transition_definition' => 'first_transition_definition',
                'display_type' => 'page',
                'frontend_options' => [
                    'class' => 'foo'
                ],
                'page_template' => '@OroWorkflow/Test/pageTemplate.html.twig',
                'dialog_template' => '@OroWorkflow/Test/dialogTemplate.html.twig',
                'message_parameters' => ['test' => 'param'],
                'form_type' => 'custom_workflow_transition',
                'destination_page' => 'name',
                'form_options' => [
                    'attribute_fields' => [
                        'first_attribute' => [
                            'form_type' => TextType::class,
                            'label' => 'First Attribute',
                            'options' => [
                                'required' => 1
                            ]
                        ]
                    ]
                ],
                'init_entities' => ['entity1'],
                'init_routes' => ['route1'],
                'init_datagrids' => ['datagrid1'],
                'init_context_attribute' => 'test_init_context',
                'triggers' => [
                    [
                        'event' => 'create',
                        'entity_class' => 'Other\Entity',
                        'relation' => 'firstEntity',
                        'require' => 'entity.firstEntity.id === main_entity.id',
                        'queued' => true,
                        'field' => null,
                        'cron' => null,
                        'filter' => null
                    ],
                    [
                        'event' => 'update',
                        'field' => 'description',
                        'require' => 'entity === main_entity',
                        'queued' => true,
                        'entity_class' => null,
                        'relation' => null,
                        'cron' => null,
                        'filter' => null
                    ],
                    [
                        'event' => 'delete',
                        'entity_class' => 'Other\Entity',
                        'relation' => 'firstEntity',
                        'require' => 'not empty(entity.firstEntity) && attributes["first_attribute"] == "ok"',
                        'queued' => true,
                        'field' => null,
                        'cron' => null,
                        'filter' => null
                    ],
                    [
                        'cron' => '1 * * * *',
                        'filter' => 'e.text = "string"',
                        'queued' => true,
                        'entity_class' => null,
                        'relation' => null,
                        'event' => null,
                        'field' => null,
                        'require' => null
                    ]
                ]
            ],
            'transition_with_form_options_configuration' =>
                [
                    'step_to' => 'first_step',
                    'conditional_steps_to' => [],
                    'transition_definition' => 'first_transition_definition',
                    'is_start' => false,
                    'is_hidden' => false,
                    'is_unavailable_hidden' => false,
                    'acl_message' => null,
                    'frontend_options' => [],
                    'form_type' => WorkflowTransitionType::class,
                    'display_type' => 'dialog',
                    'message_parameters' => [],
                    'form_options' => [
                        WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION => [
                            'handler' => 'handler',
                            'template' => 'template',
                            'data_provider' => 'data_provider',
                            'data_attribute' => 'form_data',
                        ],
                    ],
                    'page_template' => null,
                    'dialog_template' => null,
                    'init_entities' => [],
                    'init_routes' => [],
                    'init_datagrids' => [],
                    'init_context_attribute' => 'init_context',
                    'triggers' => [],
                    'destination_page' => '',
                ],
            'transition_with_form_options_configuration_defaults' =>
                [
                    'step_to' => 'first_step',
                    'conditional_steps_to' => [],
                    'transition_definition' => 'first_transition_definition',
                    'is_start' => false,
                    'is_hidden' => false,
                    'is_unavailable_hidden' => false,
                    'acl_message' => null,
                    'frontend_options' => [],
                    'form_type' => WorkflowTransitionType::class,
                    'display_type' => 'dialog',
                    'form_options' => [
                        WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION => [
                            'handler' => WorkflowConfiguration::DEFAULT_FORM_CONFIGURATION_HANDLER,
                            'template' => WorkflowConfiguration::DEFAULT_FORM_CONFIGURATION_TEMPLATE,
                            'data_provider' => 'data_provider',
                            'data_attribute' => 'form_data',
                        ],
                    ],
                    'page_template' => null,
                    'dialog_template' => null,
                    'init_entities' => [],
                    'init_routes' => [],
                    'init_datagrids' => [],
                    'init_context_attribute' => 'init_context',
                    'triggers' => [],
                    'destination_page' => '',
                    'message_parameters' => [],
                ],
        ],
        'transition_definitions' => [
            'first_transition_definition' => [
                'preactions' => [
                    [
                        '@custom_action' => null
                    ]
                ],
                'preconditions' => [
                    '@true' => null,
                ],
                'conditions' => [
                    '@and' => [
                        '@true' => null,
                        '@or' => [
                            'parameters' => [
                                '@true' => null,
                                '@equals' => [
                                    'parameters' => [1, 1],
                                    'message' => 'Not equals'
                                ]
                            ]
                        ],
                        'message' => 'Fail upper level'
                    ]
                ],
                'actions' => [
                    ['@custom_action2' => null],
                ]
            ]
        ],
        'entity_restrictions' => [],
        'exclusive_active_groups' => ['active_group1'],
        'exclusive_record_groups' => ['record_group1'],
        WorkflowConfiguration::NODE_APPLICATIONS => [CurrentApplicationProviderInterface::DEFAULT_APPLICATION],
        'force_autostart' => false,
    ],
    'second_workflow' => [
        'entity' => 'Second\Entity',
        'is_system' => false,
        'start_step' => 'second_step',
        'entity_attribute' => 'entity',
        'steps_display_ordered' => false,
        'priority' => 0,
        'scopes' => [],
        'datagrids' => [],
        'defaults' => [
            'active' => false,
        ],
        'metadata' => [],
        'steps' => [
            'second_step' => [
                'order' => 1,
                'is_final' => false,
                'allowed_transitions' => [],
                '_is_start' => '',
                'entity_acl' => [],
                'position' => []
            ]
        ],
        'disable_operations' => [],
        'attributes' => [],
        'transitions' => [
            'second_transition' => [
                'step_to' => 'second_step',
                'conditional_steps_to' => [],
                'is_start' => false,
                'is_hidden' => false,
                'is_unavailable_hidden' => false,
                'acl_message' => null,
                'transition_definition' => 'second_transition_definition',
                'display_type' => 'dialog',
                'frontend_options' => [
                    'icon' => 'bar'
                ],
                'destination_page' => '',
                'page_template' => null,
                'dialog_template' => null,
                'form_type' => WorkflowTransitionType::class,
                'form_options' => [],
                'triggers' => [],
                'init_entities' => [],
                'init_routes' => [],
                'init_datagrids' => [],
                'init_context_attribute' => 'init_context',
                'message_parameters' => [],
            ]
        ],
        'transition_definitions' => [
            'second_transition_definition' => [
                'preactions' => [],
                'preconditions' => [],
                'conditions' => [],
                'actions' => []
            ]
        ],
        WorkflowConfiguration::NODE_APPLICATIONS => ['other_application'],
        'entity_restrictions' => [],
        'exclusive_active_groups' => [],
        'exclusive_record_groups' => [],
        'force_autostart' => true,
    ]
];
