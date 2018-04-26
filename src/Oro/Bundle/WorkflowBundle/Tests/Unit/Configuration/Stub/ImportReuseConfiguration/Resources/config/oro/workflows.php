<?php
return [
    'workflow_with_config_reuse' => [
        'entity' => 'Some\Another\Entity',
        'start_step' => 'step_a',
        'steps' => [
            'step_a' => [
                'allowed_transitions' => ['transition_one'],
                'order' => 10,
                'is_final' => false,
                '_is_start' => false,
                'entity_acl' => [],
                'position' => [],
            ],
            'step_b' => [
                'order' => 5,
                'is_final' => true,
                'allowed_transitions' => [],
                '_is_start' => false,
                'entity_acl' => [],
                'position' => [],
            ],
            'step_z' => [
                'order' => 42,
                'allowed_transitions' => [],
                'is_final' => false,
                '_is_start' => false,
                'entity_acl' => [],
                'position' => []
            ]
        ],
        'attributes' => [
            'attribute1' => [
                'type' => 'string',
                'property_path' => null,
                'options' => [],
            ]
        ],
        'transitions' => [
            'transition_one' => [
                'step_to' => 'step_b',
                'is_start' => false,
                'is_hidden' => false,
                'is_unavailable_hidden' => false,
                'acl_message' => null,
                'frontend_options' => [
                    'icon' => 'foo',
                    'message' => 'hello'
                ],
                'form_type' => 'Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType',
                'display_type' => 'dialog',
                'destination_page' => '',
                'form_options' => [],
                'page_template' => null,
                'dialog_template' => null,
                'init_entities' => [],
                'init_routes' => [],
                'init_datagrids' => [],
                'init_context_attribute' => 'init_context',
                'message_parameters' => [],
                'triggers' => [],
                'transition_definition' => 'transition_one_definition'
            ]
        ],
        'is_system' => false,
        'force_autostart' => false,
        'entity_attribute' => 'entity',
        'steps_display_ordered' => false,
        'defaults' => ['active' => false],
        'priority' => 0,
        'scopes' => [],
        'datagrids' => [],
        'disable_operations' => [],
        'applications' => ['default'],
        'transition_definitions' => [
            'transition_one_definition' => [
                'preactions' => [],
                'preconditions' => [],
                'conditions' => [],
                'actions' => [],
            ]
        ],
        'entity_restrictions' => [],
        'exclusive_active_groups' => [],
        'exclusive_record_groups' => [],
    ]
];
