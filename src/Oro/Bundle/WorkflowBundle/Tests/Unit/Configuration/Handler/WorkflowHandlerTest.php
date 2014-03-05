<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\WorkflowHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class WorkflowHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new WorkflowHandler();
    }

    /**
     * @param array $expected
     * @param array $input
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $expected, array $input)
    {
        $otherHandler = $this->getMock('Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface');
        $otherHandler->expects($this->once())->method('handle')->with($expected)
            ->will($this->returnValue($expected));

        $this->handler->addHandler($otherHandler);

        $this->assertEquals($expected, $this->handler->handle($input));
    }

    /**
     * @return array
     */
    public function handleDataProvider()
    {
        return array(
            'simple configuration' => array(
                'expected' => array(
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity' => '\DateTime',
                ),
                'input' => array(
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity' => '\DateTime',
                ),
            ),
            'filtered configuration' => array(
                'expected' => array(
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity' => '\DateTime',
                    'is_system' => false,
                    'start_step' => null,
                    'entity_attribute' => 'entity',
                    'steps_display_ordered' => true,
                    WorkflowConfiguration::NODE_STEPS => array(),
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(),
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => array(),
                ),
                'input' => array(
                    'name' => 'test_workflow',
                    'label' => 'Test Workflow',
                    'entity' => '\DateTime',
                    'is_system' => false,
                    'start_step' => null,
                    'entity_attribute' => 'entity',
                    'steps_display_ordered' => true,
                    WorkflowConfiguration::NODE_STEPS => array(),
                    WorkflowConfiguration::NODE_ATTRIBUTES => array(),
                    WorkflowConfiguration::NODE_TRANSITIONS => array(),
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => array(),
                    'unknown_first' => 'first_value',
                    'unknown_second' => 'second_value',
                ),
            )
        );
    }

    public function testHandleEmptyConfiguration()
    {
        $configuration = array(
            'entity' => 'NotExistingEntity',
        );

        $result = $this->handler->handle($configuration);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertArrayNotHasKey('entity', $result);

        $this->assertStringStartsWith('workflow_', $result['name']);
        $this->assertEquals($result['name'], $result['label']);
    }
}
