<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutDialogResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Layout\LayoutDialogDataStartTransitionProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class LayoutDialogDataStartTransitionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutDialogDataStartTransitionProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new LayoutDialogDataStartTransitionProcessor();
    }

    public function testData()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('get')->with('entityId', 0)->willReturn(42);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->createMock(FormView::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('createView')->willReturn($formView);

        $context = new TransitionContext();
        $context->setResultType(new LayoutDialogResultType('route_name'));
        $context->setWorkflowItem($workflowItem);
        $context->setTransition($transition);
        $context->setRequest($request);
        $context->setForm($form);
        $context->setTransitionName('transitionName');
        $context->setWorkflowName('workflowName');

        $this->processor->process($context);

        $this->assertSame(
            [
                'data' => [
                    'workflowName' => 'workflowName',
                    'workflowItem' => $workflowItem,
                    'transition' => $transition,
                    'transitionName' => 'transitionName',
                    'transitionFormView' => $formView,
                    'entityId' => 42,
                    'formRouteName' => 'route_name',
                    'originalUrl' => null
                ]
            ],
            $context->getResult()
        );

        $this->assertTrue($context->isProcessed());
    }

    public function skipUnsupportedResultTypeContext()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())->method('getWorkflowName');

        $this->processor->process($context);
    }
}
