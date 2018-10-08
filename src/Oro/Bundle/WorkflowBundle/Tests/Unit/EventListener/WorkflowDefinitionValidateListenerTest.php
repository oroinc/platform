<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowDefinitionValidateListener;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Component\Action\Exception\AssemblerException;

class WorkflowDefinitionValidateListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowAssembler|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowAssembler;

    /** @var WorkflowDefinitionValidateListener */
    protected $listener;

    protected function setUp()
    {
        $this->workflowAssembler = $this->createMock(WorkflowAssembler::class);
        $this->listener = new WorkflowDefinitionValidateListener($this->workflowAssembler);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage test message
     */
    public function testOnUpdateWorkflowDefinition()
    {
        $this->workflowAssembler->method('assemble')
            ->will($this->throwException(new AssemblerException('test message')));
        $this->listener->onCreateWorkflowDefinition($this->getEvent());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage test message
     */
    public function testOnCreateWorkflowDefinition()
    {
        $this->workflowAssembler->method('assemble')
            ->will($this->throwException(new AssemblerException('test message')));
        $this->listener->onUpdateWorkflowDefinition($this->getEvent());
    }

    /**
     * @return WorkflowChangesEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEvent()
    {
        $event = $this->createMock(WorkflowChangesEvent::class);
        $event->expects($this->any())->method('getDefinition')->willReturn(
            $this->createMock(WorkflowDefinition::class)
        );

        return $event;
    }
}
