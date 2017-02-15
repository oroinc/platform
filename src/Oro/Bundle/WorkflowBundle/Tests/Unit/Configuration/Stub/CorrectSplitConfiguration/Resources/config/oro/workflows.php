<?php

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

return array(
    'first_workflow' => array(
        'entity' => 'First\Entity',
        'is_system' => true,
        'start_step' => 'first_step',
        'entity_attribute' => 'my_entity',
        'steps_display_ordered' => true,
        'steps' => array(
            'first_step' => array(
                'order' => 1,
                'is_final' => true,
                'entity_acl' => array(
                    'first_attribute' => array('update' => false, 'delete' => true)
                ),
                'allowed_transitions' => array('first_transition'),
            )
        ),
        'attributes' => array(
            'first_attribute' => array(
                'type' => 'object',
                'options' => array(
                    'class' => 'DateTime'
                ),
                'property_path' => null
            ),
            'second_attribute' => array(
                'type' => 'entity',
                'entity_acl' => array(
                    'update' => true,
                    'delete' => false,
                ),
                'property_path' => 'first_attribute.test',
                'options' => array(
                    'class' => 'DateTime',
                )
            )
        ),
        'transitions' => array(
            'first_transition' => array(
                'step_to' => 'first_step',
                'is_start' => true,
                'is_hidden' => true,
                'is_unavailable_hidden' => true,
                'acl_resource' => 'some_acl',
                'acl_message' => 'Test ACL message',
                'transition_definition' => 'first_transition_definition',
                'display_type' => 'page',
                'frontend_options' => array(
                    'class' => 'foo'
                ),
                'page_template' => 'Workflow:Test:pageTemplate.html.twig',
                'dialog_template' => 'Workflow:Test:dialogTemplate.html.twig',
                'form_type' => 'custom_workflow_transition',
                'form_options' => array(
                    'attribute_fields' => array(
                        'first_attribute' => array(
                            'form_type' => 'text',
                            'label' => 'First Attribute',
                            'options' => array(
                                'required' => 1
                            )
                        )
                    )
                ),
            ),
            'transition_with_page_form_configuration' =>
                array(
                    'step_to' => 'first_step',
                    'transition_definition' => 'first_transition_definition',
                    \Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration::NODE_PAGE_FORM_CONFIGURATION =>
                        array(
                            'handler' => 'handler',
                            'template' => 'template',
                            'data_provider' => 'data_provider',
                            'data_attribute' => 'data_attribute',
                        ),
                    'is_start' => false,
                    'is_hidden' => false,
                    'is_unavailable_hidden' => false,
                    'acl_message' => null,
                    'frontend_options' => array(),
                    'form_type' => 'oro_workflow_transition',
                    'display_type' => 'dialog',
                    'form_options' => array(),
                    'page_template' => null,
                    'dialog_template' => null,
                    'init_entities' => array(),
                    'init_routes' => array(),
                    'init_datagrids' => array(),
                    'init_context_attribute' => 'init_context',
                    'triggers' => array(),
                ),

        ),
        'transition_definitions' => array(
            'first_transition_definition' => array(
                'preactions' => array(
                    array(
                        '@custom_action' => null
                    )
                ),
                'preconditions' => array(
                    '@true' => null
                ),
                'conditions' => array(
                    '@and' => array(
                        '@true' => null,
                        '@or' => array(
                            'parameters' => array(
                                '@true' => null,
                                '@equals' => array(
                                    'parameters' => array(1, 1),
                                    'message' => 'Not equals'
                                )
                            )
                        ),
                        'message' => 'Fail upper level'
                    )
                ),
                'actions' => array(
                    array(
                        '@custom_action' => null
                    )
                )
            )
        ),
    ),
    'second_workflow' => array(
        'entity' => 'Second\Entity',
        'is_system' => false,
        'start_step' => 'second_step',
        'entity_attribute' => 'entity',
        'steps_display_ordered' => false,
        'steps' => array(
            'second_step' => array(
                'order' => 1,
                'is_final' => false,
                'allowed_transitions' => array(),
                'entity_acl' => array(),
            )
        ),
        'attributes' => array(),
        'transitions' => array(
            'second_transition' => array(
                'step_to' => 'second_step',
                'is_start' => false,
                'is_hidden' => false,
                'is_unavailable_hidden' => false,
                'acl_resource' => null,
                'acl_message' => null,
                'transition_definition' => 'second_transition_definition',
                'display_type' => 'dialog',
                'frontend_options' => array(
                    'icon' => 'bar'
                ),
                'page_template' => null,
                'dialog_template' => null,
                'form_type' => WorkflowTransitionType::NAME,
                'form_options' => array(),
            )
        ),
        'transition_definitions' => array(
            'second_transition_definition' => array(
                'preactions' => array(),
                'preconditions' => array(),
                'conditions' => array(),
                'actions' => array()
            )
        ),
        'applications' => ['other_application'],
    )
);
