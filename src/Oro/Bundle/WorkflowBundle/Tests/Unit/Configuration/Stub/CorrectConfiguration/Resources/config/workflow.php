<?php

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepType;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

return array(
    'first_workflow' => array(
        'label' => 'First Workflow',
        'is_system' => true,
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
                '_is_start' => '',
                'position' => []
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
                'property_path' => 'first_attribute.test',
                'entity_acl' => array(
                    'update' => true,
                    'delete' => false,
                ),
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
                'frontend_options' => array(
                    'class' => 'foo'
                ),
                'form_type' => 'custom_workflow_transition',
                'display_type' => 'page',
                'page_template' => 'Workflow:Test:pageTemplate.html.twig',
                'dialog_template' => 'Workflow:Test:dialogTemplate.html.twig',
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
                'schedule' => [
                    'cron' => '1 * * * *',
                    'filter' => "e.field < DATE_ADD(NOW(), 1, 'day')"
                ]
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
        'steps' => array(
            'second_step' => array(
                'label' => 'Second Step',
                'order' => 1,
                'is_final' => false,
                'allowed_transitions' => array(),
                '_is_start' => '',
                'entity_acl' => array(),
                'position' => []
            )
        ),
        'attributes' => array(),
        'transitions' => array(
            'second_transition' => array(
                'label' => 'Second Transition',
                'step_to' => 'second_step',
                'transition_definition' => 'second_transition_definition',
                'frontend_options' => array(
                    'icon' => 'bar'
                ),
                'is_start' => false,
                'is_hidden' => false,
                'is_unavailable_hidden' => false,
                'acl_resource' => null,
                'acl_message' => null,
                'message' => null,
                'form_type' => WorkflowTransitionType::NAME,
                'display_type' => 'dialog',
                'form_options' => array(),
                'page_template' => null,
                'dialog_template' => null,
            )
        ),
        'transition_definitions' => array(
            'second_transition_definition' => array(
                'pre_conditions' => array(),
                'conditions' => array(),
                'post_actions' => array()
            )
        ),
        'is_system' => false,
        'entity_attribute' => 'entity',
        'steps_display_ordered' => false,
    )
);
