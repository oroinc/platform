<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\StepHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class StepHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StepHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new StepHandler();
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
                    'start_step' => 'first_step',
                    WorkflowConfiguration::NODE_STEPS => array(
                        array(
                            'name' => 'first_step',
                            'label' => 'First Step',
                            'order' => 10,
                            'is_final' => true,
                            'allowed_transitions' => array(),
                        ),
                    ),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(
                        array(
                            'name' => 'start_transition',
                            'is_start' => true,
                        ),
                    ),
                ),
                'input' => array(
                    'start_step' => 'first_step',
                    WorkflowConfiguration::NODE_STEPS => array(
                        array(
                            'name' => 'step:starting_point',
                            'order' => -1,
                            '_is_start' => true,
                            'is_final' => false,
                            'allowed_transitions' => array('start_transition'),
                        ),
                        array(
                            'name' => 'first_step',
                            'label' => 'First Step',
                            'order' => 10,
                            '_is_start' => false,
                            'is_final' => true,
                        ),
                    ),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(
                        array(
                            'name' => 'start_transition'
                        ),
                    ),
                ),
            ),
            'full configuration' => array(
                'expected' => array(
                    'start_step' => null,
                    WorkflowConfiguration::NODE_STEPS => array(
                        array(
                            'name' => 'first_step',
                            'label' => 'First Step',
                            'order' => 10,
                            'is_final' => false,
                            'entity_acl' => array('attribute' => array('delete' => false)),
                            'allowed_transitions' => array('regular_transition'),
                        ),
                        array(
                            'name' => 'second_step',
                            'label' => 'second_step',
                            'order' => 20,
                            'is_final' => true,
                            'allowed_transitions' => array(),
                        ),
                    ),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(
                        array(
                            'name' => 'start_transition',
                            'is_start' => true,
                        ),
                        array(
                            'name' => 'regular_transition',
                            'is_start' => false,
                        ),
                    ),
                ),
                'input' => array(
                    'start_step' => 'unknown_step',
                    WorkflowConfiguration::NODE_STEPS => array(
                        array(
                            'name' => 'step:starting_point',
                            'order' => -1,
                            '_is_start' => true,
                            'is_final' => false,
                            'allowed_transitions' => array('start_transition'),
                        ),
                        array(
                            'name' => 'first_step',
                            'label' => 'First Step',
                            'order' => 10,
                            '_is_start' => false,
                            'is_final' => false,
                            'entity_acl' => array('attribute' => array('delete' => false)),
                            'allowed_transitions' => array('regular_transition', 'unknown_transition'),
                            'unknown_first' => 'first_value',
                            'unknown_second' => 'second_value',
                        ),
                        array(
                            'name' => 'second_step',
                            'order' => 20,
                            '_is_start' => false,
                            'is_final' => true,
                        ),
                    ),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(
                        array(
                            'name' => 'start_transition',
                            'is_start' => true,
                        ),
                        array(
                            'name' => 'regular_transition',
                            'is_start' => true,
                        ),
                    ),
                ),
            ),
        );
    }

    public function testHandleEmptyConfiguration()
    {
        $configuration = array(
            WorkflowConfiguration::NODE_STEPS => array(
                array('is_final' => true)
            ),
        );

        $result = $this->handler->handle($configuration);

        $steps = $result[WorkflowConfiguration::NODE_STEPS];
        $this->assertCount(1, $steps);
        $step = current($steps);

        $this->assertArrayHasKey('name', $step);
        $this->assertArrayHasKey('label', $step);

        $this->assertStringStartsWith('step_', $step['name']);
        $this->assertEquals($step['name'], $step['label']);
    }
}
