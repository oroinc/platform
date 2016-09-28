<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowTransitionTriggersListener;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggersUpdater;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggersBag;
use Oro\Bundle\WorkflowBundle\Model\WorkflowTransitionTriggersAssembler;
use Oro\Component\Testing\Unit\EntityTrait;

class WorkflowTransitionTriggersListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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
                WorkflowEvents::WORKFLOW_BEFORE_CREATE => 'createTriggers',
                WorkflowEvents::WORKFLOW_AFTER_CREATE => 'updateTriggers',
                WorkflowEvents::WORKFLOW_BEFORE_UPDATE => 'createTriggers',
                WorkflowEvents::WORKFLOW_AFTER_UPDATE => 'updateTriggers',
                WorkflowEvents::WORKFLOW_AFTER_DELETE => 'deleteTriggers',
                WorkflowEvents::WORKFLOW_DEACTIVATED => 'deleteTriggers',
                WorkflowEvents::WORKFLOW_ACTIVATED => [
                    ['createTriggers', 10],
                    ['updateTriggers', -10]
                ]
            ],
            WorkflowTransitionTriggersListener::getSubscribedEvents()
        );

        foreach (array_values(WorkflowTransitionTriggersListener::getSubscribedEvents()) as $call) {
            if (is_string($call)) {
                $this->assertTrue(method_exists($this->listener, $call));
            } elseif (is_array($call)) {
                foreach ($call as $method) {
                    list($method, $priority) = $method;
                    $this->assertTrue(method_exists($this->listener, $method));
                }
            }
        }
    }

    public function testCreateAndUpdateTriggers()
    {
        $definition = (new WorkflowDefinition())->setName('test');

        $trigger1 = new TransitionCronTrigger();
        $trigger2 = new TransitionEventTrigger();

        $event = new WorkflowChangesEvent($definition);

        $this->assembler->expects($this->once())->method('assembleTriggers')->with($definition)->willReturn(
            [
                $trigger1,
                $trigger2
            ]
        );

        $this->listener->createTriggers($event);//pre event job

        $triggersBag = new TriggersBag($definition, [$trigger1, $trigger2]);

        $this->updater->expects($this->once())
            ->method('updateTriggers')
            ->with($triggersBag);

        $this->listener->updateTriggers($event);//after event job

    }

    public function testTriggersDelete()
    {
        $definition = new WorkflowDefinition();
        $event = new WorkflowChangesEvent($definition);

        $this->updater->expects($this->once())
            ->method('removeTriggers')->with($definition);

        $this->listener->deleteTriggers($event);
    }
}
