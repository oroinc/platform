<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\StartHandleProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartHandleProcessorTest extends TestCase
{
    private WorkflowManager&MockObject $workflowManager;
    private StartHandleProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->processor = new StartHandleProcessor($this->workflowManager);
    }

    public function testHandling(): void
    {
        $entity = (object)['id' => 42];

        $initialWorkflowItem = $this->createMock(WorkflowItem::class);
        $initialWorkflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $initialWorkflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $newWorkflowItem = $this->createMock(WorkflowItem::class);

        $transition = $this->createMock(Transition::class);

        $initData = ['initial data'];

        $context = new TransitionContext();
        $context->setSaved(true);
        $context->setWorkflowItem($initialWorkflowItem);
        $context->setWorkflowName('workflow name');
        $context->setTransition($transition);
        $context->set(TransitionContext::INIT_DATA, $initData);

        $this->workflowManager->expects($this->once())
            ->method('startWorkflow')
            ->with('workflow name', $entity, $transition, $initData)
            ->willReturn($newWorkflowItem);

        $this->processor->process($context);
    }

    public function testErrorProcessing(): void
    {
        $entity = (object)['id' => 42];

        $initialWorkflowItem = $this->createMock(WorkflowItem::class);
        $initialWorkflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $initialWorkflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $transition = $this->createMock(Transition::class);
        $initData = ['initial data'];

        $context = new TransitionContext();
        $context->setSaved(true);
        $context->setWorkflowItem($initialWorkflowItem);
        $context->setWorkflowName('workflow name');
        $context->setTransition($transition);
        $context->set(TransitionContext::INIT_DATA, $initData);

        $exception = new \Exception('something happens while transit');

        $this->workflowManager->expects($this->once())
            ->method('startWorkflow')
            ->with('workflow name', $entity, $transition, $initData)
            ->willThrowException($exception);

        $this->processor->process($context);

        $this->assertSame($exception, $context->getError());
        $this->assertEquals('normalize', $context->getFirstGroup());
    }

    public function testSkipNotSavedForm(): void
    {
        $context = new TransitionContext();
        $this->assertFalse($context->isSaved(), 'Context must be not saved by default');
        $this->workflowManager->expects($this->never())
            ->method('startWorkflow');
        $this->processor->process($context);
    }

    public function testSkipContextWithFailures(): void
    {
        $context = new TransitionContext();
        $context->setSaved(true);
        $context->setError(new \Exception('something happens'));
        $this->workflowManager->expects($this->never())
            ->method('startWorkflow');
        $this->processor->process($context);
    }
}
