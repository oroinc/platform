<?php

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

return array(
    'first_workflow' => array(
        'label' => 'First Workflow',
        'entity' => 'First\Entity',
        'start_step' => 'first_step',
        'entity_attribute' => 'my_entity',
        'steps_display_ordered' => true,
        'steps' => array(
            'first_step' => array(
                'label' => 'First Step',
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
                'label' => 'First Attribute',
                'type' => 'object',
                'options' => array(
                    'class' => 'DateTime'
                ),
                'property_path' => null
            ),
            'second_attribute' => array(
                'label' => 'Second Attribute',
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
                'label' => 'First Transition',
                'step_to' => 'first_step',
                'is_start' => true,
                'is_hidden' => true,
                'is_unavailable_hidden' => true,
                'acl_resource' => 'some_acl',
                'acl_message' => 'Test ACL message',
                'message' => 'Test message',
                'transition_definition' => 'first_transition_definition',
                'display_type' => 'page',
                'frontend_options' => array(
                    'class' => 'foo'
                ),
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
            )
        ),
        'transition_definitions' => array(
            'first_transition_definition' => array(
                'pre_conditions' => array(
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
                'post_actions' => array(
                    array(
                        '@custom_post_action' => null

                    )
                )
            )
        ),
    ),
    'second_workflow' => array(
        'label' => 'Second Workflow',
        'entity' => 'Second\Entity',
        'start_step' => 'second_step',
        'entity_attribute' => 'entity',
        'steps_display_ordered' => false,
        'steps' => array(
            'second_step' => array(
                'label' => 'Second Step',
                'order' => 1,
                'is_final' => false,
                'allowed_transitions' => array(),
                'entity_acl' => array(),
            )
        ),
        'attributes' => array(),
        'transitions' => array(
            'second_transition' => array(
                'label' => 'Second Transition',
                'step_to' => 'second_step',
                'is_start' => false,
                'is_hidden' => false,
                'is_unavailable_hidden' => false,
                'acl_resource' => null,
                'acl_message' => null,
                'message' => null,
                'transition_definition' => 'second_transition_definition',
                'display_type' => 'dialog',
                'frontend_options' => array(
                    'icon' => 'bar'
                ),
                'form_type' => WorkflowTransitionType::NAME,
                'form_options' => array(),
            )
        ),
        'transition_definitions' => array(
            'second_transition_definition' => array(
                'pre_conditions' => array(),
                'conditions' => array(),
                'post_actions' => array()
            )
        ),
    )
);
