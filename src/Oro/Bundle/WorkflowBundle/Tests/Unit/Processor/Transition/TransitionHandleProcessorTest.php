<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\TransitionHandleProcessor;

class TransitionHandleProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManager;

    /** @var TransitionHandleProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->processor = new TransitionHandleProcessor($this->workflowManager);
    }

    public function testHandle()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);

        $context = new TransitionContext();
        $context->setSaved(true);
        $context->setWorkflowItem($workflowItem);
        $context->setTransition($transition);

        $this->workflowManager->expects($this->once())->method('transit')->with($workflowItem, $transition);

        $this->processor->process($context);
    }

    public function testHandleFailures()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);

        $context = new TransitionContext();
        $context->setSaved(true);
        $context->setWorkflowItem($workflowItem);
        $context->setTransition($transition);

        $exception = new \Exception('something happens');

        $this->workflowManager->expects($this->once())
            ->method('transit')->with($workflowItem, $transition)
            ->willThrowException($exception);

        $this->processor->process($context);

        $this->assertSame($exception, $context->getError());
        $this->assertEquals('normalize', $context->getFirstGroup());
    }

    public function testSkipNotSavedForm()
    {
        $context = new TransitionContext();

        $this->assertFalse($context->isSaved(), 'Context must be not saved by default');

        $this->workflowManager->expects($this->never())->method('startWorkflow');

        $this->processor->process($context);
    }

    public function testSkipContextWithFailures()
    {
        $context = new TransitionContext();
        $context->setSaved(true);
        $context->setError(new \Exception('something happens'));

        $this->workflowManager->expects($this->never())->method('startWorkflow');
        $this->processor->process($context);
    }
}
