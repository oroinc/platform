<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;
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
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Component\Testing\Unit\EntityTrait;

class TransitionCronTriggerHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const ENTITY_CLASS = 'stdClass';
    private const WORKFLOW_NAME = 'test_workflow';
    private const TRANSITION_NAME = 'test_transition';

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowManager;

    /** @var TransitionCronTriggerHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var TransitionCronTriggerHandler */
    private $handler;

    /** @var TransitionCronTrigger */
    private $trigger;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->helper = $this->createMock(TransitionCronTriggerHelper::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->handler = new TransitionCronTriggerHandler($this->workflowManager, $this->helper, $this->featureChecker);

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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Cron trigger should be an instance of %s, %s instance given',
            TransitionCronTrigger::class,
            TransitionEventTrigger::class
        ));

        $trigger = new TransitionEventTrigger();

        $this->handler->process($trigger, TransitionTriggerMessage::create($trigger));
    }

    public function testProcessWithoutWorkflow()
    {
        $trigger = new TransitionCronTrigger();
        $trigger->setWorkflowDefinition($this->getEntity(WorkflowDefinition::class, ['name' => self::WORKFLOW_NAME]));

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with(self::WORKFLOW_NAME);
        $this->workflowManager->expects($this->never())
            ->method('massStartWorkflow');
        $this->workflowManager->expects($this->never())
            ->method('massTransit');

        $this->assertFalse($this->handler->process($trigger, TransitionTriggerMessage::create($trigger)));
    }

    public function testProcessWithoutTransition()
    {
        $trigger = new TransitionCronTrigger();
        $trigger->setWorkflowDefinition($this->getEntity(WorkflowDefinition::class, ['name' => self::WORKFLOW_NAME]));

        $workflow = $this->getWorkflowMock();

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with(self::WORKFLOW_NAME)
            ->willReturn($workflow);
        $this->workflowManager->expects($this->never())
            ->method('massStartWorkflow');
        $this->workflowManager->expects($this->never())
            ->method('massTransit');

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

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(true);

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

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(true);

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

    public function testProcessDisabledFeature()
    {
        $trigger = new TransitionCronTrigger();
        $trigger->setTransitionName(self::TRANSITION_NAME)
            ->setWorkflowDefinition($this->getEntity(WorkflowDefinition::class, ['name' => self::WORKFLOW_NAME]));

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with(self::WORKFLOW_NAME, FeatureConfigurationExtension::WORKFLOWS_NODE_NAME)
            ->willReturn(false);

        $this->helper->expects($this->never())
            ->method($this->anything());

        $this->workflowManager->expects($this->never())
            ->method($this->anything());

        $this->assertFalse($this->handler->process($trigger, TransitionTriggerMessage::create($trigger)));
    }

    /**
     * @param Transition $transition
     * @return Workflow
     */
    private function getWorkflowMock(Transition $transition = null)
    {
        $transitions = [];

        if ($transition) {
            $transitions[] = $transition;
        }

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('getName')
            ->willReturn(self::WORKFLOW_NAME);
        $workflow->expects($this->any())
            ->method('getTransitionManager')
            ->willReturn(new TransitionManager($transitions));

        return $workflow;
    }

    /**
     * @param bool $isStart
     * @return Transition
     */
    private function getTransition($isStart = false)
    {
        $transition = new Transition($this->createMock(TransitionOptionsResolver::class));

        return $transition->setName(self::TRANSITION_NAME)->setStart($isStart);
    }
}
