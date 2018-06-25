<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\StartHandleProcessor;

class StartHandleProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowManager;

    /** @var StartHandleProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->processor = new StartHandleProcessor($this->workflowManager);
    }

    public function testHandling()
    {
        $entity = (object)['id' => 42];

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $initialWorkflowItem */
        $initialWorkflowItem = $this->createMock(WorkflowItem::class);
        $initialWorkflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $initialWorkflowItem->expects($this->once())->method('getEntity')->willReturn($entity);

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $initialWorkflowItem */
        $newWorkflowItem = $this->createMock(WorkflowItem::class);

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
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

    public function testErrorProcessing()
    {
        $entity = (object)['id' => 42];

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $initialWorkflowItem */
        $initialWorkflowItem = $this->createMock(WorkflowItem::class);
        $initialWorkflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');
        $initialWorkflowItem->expects($this->once())->method('getEntity')->willReturn($entity);

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
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
