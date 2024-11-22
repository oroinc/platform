<?php

return [
    'workflow_with_numeric_array' => [
            'entity' => 'Some\\Another\\Entity',
            'start_step' => 'step_a',
            'metadata' => [],
            'steps' => [
                'step_a' => [
                    'order' => 10,
                    'is_final' => false,
                    'allowed_transitions' => [
                        0 => 'a_to_b',
                        1 => 'transition_appended',
                    ],
                    '_is_start' => false,
                    'entity_acl' => [],
                    'position' => [],
                ],
                'step_b' => [
                    'allowed_transitions' => [
                        0 => 'b_to_z',
                    ],
                    'order' => 0,
                    'is_final' => false,
                    '_is_start' => false,
                    'entity_acl' => [],
                    'position' => [],
                ],
                'step_z' => [
                    'allowed_transitions' => [],
                    'order' => 0,
                    'is_final' => false,
                    '_is_start' => false,
                    'entity_acl' => [],
                    'position' => [],
                ],
            ],
            'attributes' => [],
            'transitions' => [
                'transition_one' => [
                    'step_to' => 'step_c',
                    'conditional_steps_to' => [],
                    'transition_definition' => 'transition_one_definition',
                    'frontend_options' => [
                        'icon' => 'bar',
                        'message' => 'hello',
                    ],
                    'is_start' => false,
                    'is_hidden' => false,
                    'is_unavailable_hidden' => false,
                    'acl_message' => null,
                    'form_type' => 'Oro\\Bundle\\WorkflowBundle\\Form\\Type\\WorkflowTransitionType',
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
                ],
            ],
            'transition_definitions' => [
                'transition_one_definition' => [
                    'preactions' => [],
                    'preconditions' => [],
                    'conditions' => [],
                    'actions' => [],
                ],
            ],
            'applications' => [
                0 => 'application_a',
                1 => 'application_appended',
            ],
            'force_autostart' => false,
            'is_system' => false,
            'entity_attribute' => 'entity',
            'steps_display_ordered' => false,
            'defaults' => [
                'active' => false,
            ],
            'priority' => 0,
            'scopes' => [],
            'datagrids' => [],
            'disable_operations' => [],
            'entity_restrictions' => [],
            'exclusive_active_groups' => [],
            'exclusive_record_groups' => [],
        ],
];
