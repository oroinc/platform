<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\WorkflowHandler;

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

    public function testHandleFullConfiguration()
    {
        $configuration = array(
            'name' => 'test_workflow',
            'label' => 'Test Workflow',
            'entity' => '\DateTime',
        );

        $otherHandler = $this->getMock('Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface');
        $otherHandler->expects($this->once())->method('handle')->with($configuration)
            ->will($this->returnValue($configuration));

        $this->handler->addHandler($otherHandler);

        $this->assertEquals($configuration, $this->handler->handle($configuration));
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
