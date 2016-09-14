<?php

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

return [
    'first_workflow' => [
        'label' => 'First Workflow',
        'is_system' => true,
        'entity' => 'First\Entity',
        'start_step' => 'first_step',
        'entity_attribute' => 'my_entity',
        'steps_display_ordered' => true,
        'priority' => 1,
        'defaults' => [
            'active' => true,
        ],
        'steps' => [
            'first_step' => [
                'label' => 'First Step',
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
                'label' => 'First Attribute',
                'type' => 'object',
                'options' => [
                    'class' => 'DateTime'
                ],
                'property_path' => null
            ],
            'second_attribute' => [
                'label' => 'Second Attribute',
                'type' => 'entity',
                'property_path' => 'first_attribute.test',
                'entity_acl' => [
                    'update' => true,
                    'delete' => false,
                ],
                'options' => [
                    'class' => 'DateTime',
                ]
            ]
        ],
        'transitions' => [
            'first_transition' => [
                'label' => 'First Transition',
                'step_to' => 'first_step',
                'is_start' => true,
                'is_hidden' => true,
                'is_unavailable_hidden' => true,
                'acl_resource' => 'some_acl',
                'acl_message' => 'Test ACL message',
                'message' => 'Test message',
                'transition_definition' => 'first_transition_definition',
                'frontend_options' => [
                    'class' => 'foo'
                ],
                'form_type' => 'custom_workflow_transition',
                'display_type' => 'page',
                'page_template' => 'Workflow:Test:pageTemplate.html.twig',
                'dialog_template' => 'Workflow:Test:dialogTemplate.html.twig',
                'form_options' => [
                    'attribute_fields' => [
                        'first_attribute' => [
                            'form_type' => 'text',
                            'label' => 'First Attribute',
                            'options' => [
                                'required' => 1
                            ]
                        ]
                    ]
                ],
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
                        'queued' => null,
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
                        'queued' => null,
                        'field' => null,
                        'cron' => null,
                        'filter' => null
                    ],
                    [
                        'cron' => '1 * * * *',
                        'filter' => 'e.text = "string"',
                        'queued' => null,
                        'entity_class' => null,
                        'relation' => null,
                        'event' => null,
                        'field' => null,
                        'require' => null
                    ]
                ]
            ]
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
                    '@condition1' => null,
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
                    ['@custom_action' => null],
                ]
            ]
        ],
        'entity_restrictions' => [],
        'exclusive_active_groups' => ['active_group1'],
        'exclusive_record_groups' => ['record_group1'],
    ],
    'second_workflow' => [
        'label' => 'Second Workflow',
        'entity' => 'Second\Entity',
        'start_step' => 'second_step',
        'priority' => 0,
        'defaults' => [
            'active' => false,
        ],
        'steps' => [
            'second_step' => [
                'label' => 'Second Step',
                'order' => 1,
                'is_final' => false,
                'allowed_transitions' => [],
                '_is_start' => '',
                'entity_acl' => [],
                'position' => []
            ]
        ],
        'attributes' => [],
        'transitions' => [
            'second_transition' => [
                'label' => 'Second Transition',
                'step_to' => 'second_step',
                'transition_definition' => 'second_transition_definition',
                'frontend_options' => [
                    'icon' => 'bar'
                ],
                'is_start' => false,
                'is_hidden' => false,
                'is_unavailable_hidden' => false,
                'acl_resource' => null,
                'acl_message' => null,
                'message' => null,
                'form_type' => WorkflowTransitionType::NAME,
                'display_type' => 'dialog',
                'form_options' => [],
                'page_template' => null,
                'dialog_template' => null,
                'triggers' => []
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
        'is_system' => false,
        'entity_attribute' => 'entity',
        'steps_display_ordered' => false,
        'entity_restrictions' => [],
        'exclusive_active_groups' => [],
        'exclusive_record_groups' => [],
    ]
];
