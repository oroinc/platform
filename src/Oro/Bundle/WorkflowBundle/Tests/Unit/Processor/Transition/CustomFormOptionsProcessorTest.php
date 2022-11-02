<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\CustomFormOptionsProcessor;
use Oro\Component\Action\Action\ActionInterface;

class CustomFormOptionsProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomFormOptionsProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new CustomFormOptionsProcessor();
    }

    public function testSkipDefaultTransitionForms()
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('hasFormConfiguration')
            ->willReturn(false);

        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $context->expects($this->never())
            ->method('getWorkflowItem');

        $this->processor->process($context);
    }

    public function testWithFormInit()
    {
        $formData = (object)['id' => 42];

        $data = $this->createMock(WorkflowData::class);
        $data->expects($this->once())
            ->method('get')
            ->with('formAttribute')
            ->willReturn($formData);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('hasFormConfiguration')
            ->willReturn(true);
        $transition->expects($this->any())
            ->method('getFormOptions')
            ->willReturn(['form_init' => $action]);
        $transition->expects($this->once())
            ->method('getFormDataAttribute')
            ->willReturn('formAttribute');

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);

        $this->processor->process($context);

        $this->assertSame($formData, $context->getFormData());
    }

    public function testWithoutFormInit()
    {
        $formData = (object)['id' => 42];

        $data = $this->createMock(WorkflowData::class);
        $data->expects($this->once())
            ->method('get')
            ->with('formAttribute')
            ->willReturn($formData);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('hasFormConfiguration')
            ->willReturn(true);
        $transition->expects($this->any())
            ->method('getFormOptions')
            ->willReturn([]);
        $transition->expects($this->once())
            ->method('getFormDataAttribute')
            ->willReturn('formAttribute');

        $context = new TransitionContext();
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);

        $this->processor->process($context);

        $this->assertSame($formData, $context->getFormData());
    }
}
