<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Handler\TransitionCronTriggerHandler;
use Oro\Bundle\WorkflowBundle\Helper\TransitionCronTriggerHelper;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;
use Oro\Component\Testing\Unit\EntityTrait;

class TransitionCronTriggerHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS = 'stdClass';
    const WORKFLOW_NAME = 'test_workflow';
    const TRANSITION_NAME = 'test_transition';

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    private $workflowManager;

    /** @var TransitionCronTriggerHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $helper;

    /** @var TransitionCronTriggerHandler */
    private $handler;

    /** @var TransitionCronTrigger */
    private $trigger;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder(WorkflowManager::class)->disableOriginalConstructor()->getMock();

        $this->helper = $this->getMockBuilder(TransitionCronTriggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new TransitionCronTriggerHandler($this->workflowManager, $this->helper);

        $this->trigger = $this->getEntity(
            TransitionCronTrigger::class,
            [
                'transitionName' => self::TRANSITION_NAME,
                'workflowDefinition' => $this->getEntity(
                    WorkflowDefinition::class,
                    [
                        'name' => self::WORKFLOW_NAME,
                        'relatedEntity' => self::ENTITY_CLASS
                    ]
                )
            ]
        );
    }

    public function testProcessException()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Trigger should be instance of Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger, ' .
            'Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger instace given'
        );

        $trigger = new TransitionEventTrigger();

        $this->handler->process($trigger, TransitionTriggerMessage::create($trigger));
    }

    public function testProcessWithoutWorkflow()
    {
        $trigger = new TransitionCronTrigger();
        $trigger->setWorkflowDefinition($this->getEntity(WorkflowDefinition::class, ['name' => self::WORKFLOW_NAME]));

        $this->workflowManager->expects($this->once())->method('getWorkflow')->with(self::WORKFLOW_NAME);
        $this->workflowManager->expects($this->never())->method('massStartWorkflow');
        $this->workflowManager->expects($this->never())->method('massTransit');

        $this->assertFalse($this->handler->process($trigger, TransitionTriggerMessage::create($trigger)));
    }

    public function testProcessWithoutTransition()
    {
        $trigger = new TransitionCronTrigger();
        $trigger->setWorkflowDefinition($this->getEntity(WorkflowDefinition::class, ['name' => self::WORKFLOW_NAME]));

        $workflow = $this->getWorkflowMock();

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with(self::WORKFLOW_NAME)
            ->willReturn($workflow);
        $this->workflowManager->expects($this->never())->method('massStartWorkflow');
        $this->workflowManager->expects($this->never())->method('massTransit');

        $this->assertFalse($this->handler->process($trigger, TransitionTriggerMessage::create($trigger)));
    }

    public function testProcessStartTransition()
    {
        $trigger = new TransitionCronTrigger();
        $trigger->setTransitionName(self::TRANSITION_NAME)
            ->setWorkflowDefinition($this->getEntity(WorkflowDefinition::class, ['name' => self::WORKFLOW_NAME]));

        $workflow = $this->getWorkflowMock($this->getTransition(true));

        $entityClass = self::ENTITY_CLASS;
        $entity = new $entityClass();

        $this->helper->expects($this->once())
            ->method('fetchEntitiesWithoutWorkflowItems')
            ->with($trigger, $workflow)
            ->willReturn([$entity]);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with(self::WORKFLOW_NAME)
            ->willReturn($workflow);
        $this->workflowManager->expects($this->once())
            ->method('massStartWorkflow')
            ->with([new WorkflowStartArguments(self::WORKFLOW_NAME, $entity, [], self::TRANSITION_NAME)]);

        $this->assertTrue($this->handler->process($trigger, TransitionTriggerMessage::create($trigger)));
    }

    public function testProcessTransition()
    {
        $trigger = new TransitionCronTrigger();
        $trigger->setTransitionName(self::TRANSITION_NAME)
            ->setWorkflowDefinition($this->getEntity(WorkflowDefinition::class, ['name' => self::WORKFLOW_NAME]));

        $workflow = $this->getWorkflowMock($this->getTransition(false));
        $workflowItem = new WorkflowItem();

        $this->helper->expects($this->once())
            ->method('fetchWorkflowItemsForTrigger')
            ->with($trigger, $workflow)
            ->willReturn([$workflowItem]);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with(self::WORKFLOW_NAME)
            ->willReturn($workflow);
        $this->workflowManager->expects($this->once())
            ->method('massTransit')
            ->with([['workflowItem' => $workflowItem, 'transition' => self::TRANSITION_NAME]]);

        $this->assertTrue($this->handler->process($trigger, TransitionTriggerMessage::create($trigger)));
    }

    /**
     * @param Transition $transition
     * @return Workflow
     */
    protected function getWorkflowMock(Transition $transition = null)
    {
        $transitions = [];

        if ($transition) {
            $transitions[] = $transition;
        }

        $workflow = $this->getMockBuilder(Workflow::class)->disableOriginalConstructor()->getMock();
        $workflow->expects($this->any())->method('getName')->willReturn(self::WORKFLOW_NAME);
        $workflow->expects($this->any())
            ->method('getTransitionManager')
            ->willReturn(new TransitionManager($transitions));

        return $workflow;
    }

    /**
     * @param bool $isStart
     * @return Transition
     */
    protected function getTransition($isStart = false)
    {
        $transition = new Transition();
        $transition->setName(self::TRANSITION_NAME)->setStart($isStart);

        return $transition;
    }
}
