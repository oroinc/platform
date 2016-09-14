<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowTransitionTriggersListener;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdater;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggersBag;
use Oro\Bundle\WorkflowBundle\Model\WorkflowTransitionTriggersAssembler;

class WorkflowTransitionTriggersListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowTransitionTriggersListener */
    private $listener;

    /** @var TransitionTriggersUpdater|\PHPUnit_Framework_MockObject_MockObject */
    private $updater;

    /** @var WorkflowTransitionTriggersAssembler|\PHPUnit_Framework_MockObject_MockObject */
    private $assembler;

    protected function setUp()
    {
        $this->assembler = $this->getMockBuilder(WorkflowTransitionTriggersAssembler::class)
            ->disableOriginalConstructor()->getMock();

        $this->updater = $this->getMockBuilder(TransitionTriggersUpdater::class)
            ->disableOriginalConstructor()->getMock();

        $this->listener = new WorkflowTransitionTriggersListener($this->assembler, $this->updater);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                WorkflowEvents::WORKFLOW_AFTER_CREATE => 'triggersUpdate',
                WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'triggersUpdate',
                WorkflowEvents::WORKFLOW_AFTER_DELETE => 'triggersDelete',
                WorkflowEvents::WORKFLOW_ACTIVATED => 'triggersUpdate',
                WorkflowEvents::WORKFLOW_DEACTIVATED => 'triggersDelete'
            ],
            WorkflowTransitionTriggersListener::getSubscribedEvents()
        );

        foreach (array_unique(array_values(WorkflowTransitionTriggersListener::getSubscribedEvents())) as $method) {
            $this->assertTrue(method_exists($this->listener, $method));
        }
    }

    public function testTriggersUpdate()
    {
        $definition = new WorkflowDefinition();

        $event = new WorkflowChangesEvent($definition);

        $trigger1 = new TransitionTriggerCron();
        $trigger2 = new TransitionTriggerEvent();
        $this->assembler->expects($this->once())->method('assembleTriggers')->with($definition)->willReturn(
            [
                $trigger1,
                $trigger2
            ]
        );

        $this->updater->expects($this->once())
            ->method('updateTriggers')
            ->with(new TriggersBag($definition, [$trigger1, $trigger2]));

        $this->listener->triggersUpdate($event);
    }

    public function testTriggersDelete()
    {
        $definition = new WorkflowDefinition();
        $event = new WorkflowChangesEvent($definition);

        $this->updater->expects($this->once())
            ->method('removeTriggers')->with($definition);

        $this->listener->triggersDelete($event);
    }
}
