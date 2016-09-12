<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowTransitionTriggersListener;
use Oro\Bundle\WorkflowBundle\Model\TransitionTriggerAssembler;

class WorkflowTransitionTriggersListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowTransitionTriggersListener */
    private $listener;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var \Oro\Bundle\WorkflowBundle\Model\TransitionTriggerAssembler|\PHPUnit_Framework_MockObject_MockObject */
    private $assembler;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->assembler = $this->getMockBuilder(TransitionTriggerAssembler::class)
            ->disableOriginalConstructor()->getMock();
        $this->listener = new WorkflowTransitionTriggersListener($this->em);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                WorkflowEvents::WORKFLOW_AFTER_CREATE => 'triggersCreate',
                WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'triggersUpdate',
                WorkflowEvents::WORKFLOW_AFTER_DELETE => 'triggersDelete',
                WorkflowEvents::WORKFLOW_ACTIVATED => 'triggersCreate',
                WorkflowEvents::WORKFLOW_DEACTIVATED => 'triggersDelete'
            ],
            WorkflowTransitionTriggersListener::getSubscribedEvents()
        );

        $listener = new WorkflowTransitionTriggersListener();

        foreach (WorkflowTransitionTriggersListener::getSubscribedEvents() as $method) {
            $this->assertTrue(method_exists($listener, $method));
        }
    }

    public function testWorkflowCreated()
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

        $this->em->expects($this->exactly(2))->method('persist')->withConsecutive([
            [$this->equalTo($trigger1)],
            [$this->equalTo($trigger2)]
        ]);

        $this->em->expects($this->once())->method('flush');

        $this->listener->workflowCreated($event);
    }

    public function testWorkflowCreatedWithoutTriggers()
    {
        $definition = new WorkflowDefinition();

        $event = new WorkflowChangesEvent($definition);

        $this->assembler->expects($this->once())->method('assembleTriggers')->with($definition)->willReturn([]);

        $this->em->expects($this->never())->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->listener->workflowCreated($event);
    }

    public function testWorkflowUpdated()
    {
        $definition = new WorkflowDefinition();


    }
}
