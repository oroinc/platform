<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\TransitionHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class TransitionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TransitionHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new TransitionHandler();
    }

    /**
     * @param array $expected
     * @param array $input
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $expected, array $input)
    {
        $this->assertEquals($expected, $this->handler->handle($input));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function handleDataProvider()
    {
        return array(
            'simple configuration' => array(
                'expected' => array(
                    WorkflowConfiguration::NODE_STEPS => array(
                        array('name' => 'test_step')
                    ),
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(
                        array('name' => 'test_attribute')
                    ),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(
                        array(
                            'name' => 'test_transition',
                            'label' => 'Test Transition',
                            'step_to' => 'test_step',
                            'transition_definition' => 'test_transition_definition',
                            'form_options' => array(
                                'attribute_fields' => array(
                                    'test_attribute' => array(
                                        'options' => array(
                                            'required' => true,
                                            'constraints' => array(array('NotBlank' => null)),
                                        ),
                                    )
                                )
                            )
                        )
                    ),
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => array(
                        array('name' => 'test_transition_definition')
                    )
                ),
                'input' => array(
                    WorkflowConfiguration::NODE_STEPS => array(
                        array('name' => 'test_step')
                    ),
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(
                        array('name' => 'test_attribute')
                    ),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(
                        array(
                            'name' => 'test_transition',
                            'label' => 'Test Transition',
                            'step_to' => 'test_step',
                            'transition_definition' => 'test_transition_definition',
                            'form_options' => array(
                                'attribute_fields' => array(
                                    'test_attribute' => array(
                                        'options' => array(
                                            'required' => true
                                        ),
                                    )
                                )
                            )
                        )
                    ),
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => array(
                        array('name' => 'test_transition_definition')
                    )
                ),
            ),
            'full configuration' => array(
                'expected' => array(
                    WorkflowConfiguration::NODE_STEPS => array(
                        array('name' => 'test_step')
                    ),
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(
                        array('name' => 'test_attribute')
                    ),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(
                        array(
                            'name' => 'test_transition',
                            'label' => 'Test Transition',
                            'step_to' => 'test_step',
                            'is_start' => false,
                            'is_hidden' => false,
                            'is_unavailable_hidden' => true,
                            'acl_resource' => null,
                            'acl_message' => null,
                            'message' => null,
                            'transition_definition' => 'test_transition_definition',
                            'frontend_options' => array('class' => 'btn-primary'),
                            'form_type' => 'oro_workflow_transition',
                            'display_type' => 'dialog',
                            'form_options' => array(
                                'attribute_fields' => array(
                                    'test_attribute' => null,
                                )
                            ),
                        )
                    ),
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => array(
                        array('name' => 'test_transition_definition')
                    )
                ),
                'input' => array(
                    WorkflowConfiguration::NODE_STEPS => array(
                        array('name' => 'test_step')
                    ),
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(
                        array('name' => 'test_attribute')
                    ),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(
                        array(
                            'name' => 'test_transition',
                            'label' => 'Test Transition',
                            'step_to' => 'test_step',
                            'is_start' => false,
                            'is_hidden' => false,
                            'is_unavailable_hidden' => true,
                            'acl_resource' => null,
                            'acl_message' => null,
                            'message' => null,
                            'transition_definition' => 'test_transition_definition',
                            'frontend_options' => array('class' => 'btn-primary'),
                            'form_type' => 'oro_workflow_transition',
                            'display_type' => 'dialog',
                            'form_options' => array(
                                'attribute_fields' => array(
                                    'test_attribute' => null
                                )
                            ),
                            'unknown_first' => 'first_value',
                            'unknown_second' => 'second_value',
                        )
                    ),
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => array(
                        array('name' => 'test_transition_definition')
                    )
                ),
            )
        );
    }

    public function testHandleEmptyConfiguration()
    {
        $configuration = array(
            WorkflowConfiguration::NODE_STEPS => array(
                array('name' => 'test_step')
            ),
            WorkflowConfiguration::NODE_TRANSITIONS => array(
                array(
                    'step_to' => 'test_step',
                )
            ),
        );

        $result = $this->handler->handle($configuration);

        $transitions = $result[WorkflowConfiguration::NODE_TRANSITIONS];
        $this->assertCount(1, $transitions);
        $transition = current($transitions);

        $this->assertArrayHasKey('name', $transition);
        $this->assertArrayHasKey('label', $transition);
        $this->assertArrayHasKey('transition_definition', $transition);

        $this->assertStringStartsWith('transition_', $transition['name']);
        $this->assertEquals($transition['name'], $transition['label']);

        $this->assertStringStartsWith('transition_definition_', $transition['transition_definition']);
        $this->assertArrayHasKey(WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS, $result);

        $this->assertCount(1, $result[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS]);
        $transitionDefinition = current($result[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS]);

        $this->assertArrayHasKey('name', $transitionDefinition);
        $this->assertEquals($transition['transition_definition'], $transitionDefinition['name']);
    }
}
