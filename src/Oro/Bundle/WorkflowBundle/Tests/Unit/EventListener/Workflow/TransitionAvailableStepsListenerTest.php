<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Workflow;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreAnnounceEvent;
use Oro\Bundle\WorkflowBundle\EventListener\Workflow\TransitionAvailableStepsListener;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\ConfigExpression\ExpressionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransitionAvailableStepsListenerTest extends TestCase
{
    private ExpressionFactory&MockObject $expressionFactory;
    private TransitionAvailableStepsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->expressionFactory = $this->createMock(ExpressionFactory::class);
        $this->listener = new TransitionAvailableStepsListener($this->expressionFactory);
    }

    public function testOnPreAnnounceWhenNotAllowed(): void
    {
        $transition = $this->createMock(Transition::class);
        $event = new PreAnnounceEvent($this->createMock(WorkflowItem::class), $transition, false);

        $this->expressionFactory->expects($this->never())
            ->method('create');

        $this->listener->onPreAnnounce($event);
    }

    public function testOnPreAnnounceWhenAllowedWithNoConditionalSteps(): void
    {
        $stepTo = $this->createMock(Step::class);
        $stepTo->expects(self::any())
            ->method('getName')
            ->willReturn('step_to');

        $transition = $this->createMock(Transition::class);
        $transition->expects(self::any())
            ->method('getConditionalStepsTo')
            ->willReturn([]);
        $transition->expects(self::any())
            ->method('getStepTo')
            ->willReturn($stepTo);
        $transition->expects(self::any())
            ->method('getName')
            ->willReturn('transition_name');

        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreAnnounceEvent($workflowItem, $transition, true);

        $expression = $this->createMock(ExpressionInterface::class);
        $expression->expects($this->once())
            ->method('evaluate')
            ->with($workflowItem)
            ->willReturn(true);

        $this->expressionFactory->expects($this->once())
            ->method('create')
            ->with('is_granted_workflow_transition', ['transition_name', 'step_to'])
            ->willReturn($expression);

        $this->listener->onPreAnnounce($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testOnPreAnnounceWhenAllowedWithConditionalSteps(): void
    {
        $stepTo = $this->createMock(Step::class);
        $stepTo->expects(self::any())
            ->method('getName')
            ->willReturn('step_to');

        $transition = $this->createMock(Transition::class);
        $transition->expects(self::any())
            ->method('getConditionalStepsTo')
            ->willReturn(['conditional_step' => []]);
        $transition->expects(self::any())
            ->method('getStepTo')
            ->willReturn($stepTo);
        $transition->expects(self::any())
            ->method('getName')
            ->willReturn('transition_name');

        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreAnnounceEvent($workflowItem, $transition, true);

        $expression = $this->createMock(ExpressionInterface::class);
        $expression->expects($this->exactly(2))
            ->method('evaluate')
            ->withConsecutive([$workflowItem], [$workflowItem])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->expressionFactory->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['is_granted_workflow_transition', ['transition_name', 'step_to']],
                ['is_granted_workflow_transition', ['transition_name', 'conditional_step']]
            )
            ->willReturn($expression);

        $this->listener->onPreAnnounce($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testOnPreAnnounceWhenNotAllowedAfterEvaluation(): void
    {
        $stepTo = $this->createMock(Step::class);
        $stepTo->expects(self::any())
            ->method('getName')
            ->willReturn('step_to');

        $transition = $this->createMock(Transition::class);
        $transition->expects(self::any())
            ->method('getConditionalStepsTo')
            ->willReturn(['conditional_step' => []]);
        $transition->expects(self::any())
            ->method('getStepTo')
            ->willReturn($stepTo);
        $transition->expects(self::any())
            ->method('getName')
            ->willReturn('transition_name');

        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreAnnounceEvent($workflowItem, $transition, true);

        $expression = $this->createMock(ExpressionInterface::class);
        $expression->expects($this->exactly(2))
            ->method('evaluate')
            ->with($workflowItem)
            ->willReturn(false);

        $this->expressionFactory->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['is_granted_workflow_transition', ['transition_name', 'step_to']],
                ['is_granted_workflow_transition', ['transition_name', 'conditional_step']]
            )
            ->willReturn($expression);

        $this->listener->onPreAnnounce($event);

        $this->assertFalse($event->isAllowed());
    }
}
