<?php

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

return [
    'first_workflow' => [
        'entity' => 'First\Entity',
        'start_step' => 'first_step',
        'entity_attribute' => 'my_entity',
        'steps_display_ordered' => true,
        'steps' => [
            'first_step' => [
                'order' => 1,
                'is_final' => true,
                'entity_acl' => [
                    'first_attribute' => ['update' => false, 'delete' => true],
                ],
                'allowed_transitions' => ['first_transition'],
            ],
        ],
        'attributes' => [
            'first_attribute' => [
                'type' => 'object',
                'options' => [
                    'class' => 'DateTime',
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
            ],
        ],
        'transitions' => [
            'first_transition' => [
                'step_to' => 'first_step',
                'is_start' => true,
                'is_hidden' => true,
                'is_unavailable_hidden' => true,
                'acl_resource' => 'some_acl',
                'acl_message' => 'Test ACL message',
                'transition_definition' => 'first_transition_definition',
                'display_type' => 'page',
                'frontend_options' => [
                    'class' => 'foo',
                ],
                'form_type' => 'custom_workflow_transition',
                'form_options' => [
                    'attribute_fields' => [
                        'first_attribute' => [
                            'form_type' => 'text',
                            'label' => 'First Attribute',
                            'options' => [
                                'required' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'transition_definitions' => [
            'first_transition_definition' => [
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
                                    'message' => 'Not equals',
                                ],
                            ],
                        ],
                        'message' => 'Fail upper level',
                    ],
                ],
                'actions' => [
                    [
                        '@custom_action' => null,

                    ],
                ],
            ],
        ],
    ],
    'second_workflow' => [
        'entity' => 'Second\Entity',
        'start_step' => 'second_step',
        'entity_attribute' => 'entity',
        'steps_display_ordered' => false,
        'steps' => [
            'second_step' => [
                'order' => 1,
                'is_final' => false,
                'allowed_transitions' => [],
                'entity_acl' => [],
            ],
        ],
        'attributes' => [],
        'transitions' => [
            'second_transition' => [
                'step_to' => 'second_step',
                'is_start' => false,
                'is_hidden' => false,
                'is_unavailable_hidden' => false,
                'acl_resource' => null,
                'acl_message' => null,
                'transition_definition' => 'second_transition_definition',
                'display_type' => 'dialog',
                'frontend_options' => [
                    'icon' => 'bar',
                ],
                'form_type' => WorkflowTransitionType::NAME,
                'form_options' => [],
            ],
        ],
        'transition_definitions' => [
            'second_transition_definition' => [
                'preconditions' => [],
                'conditions' => [],
                'actions' => [],
            ],
        ],
    ],
];
