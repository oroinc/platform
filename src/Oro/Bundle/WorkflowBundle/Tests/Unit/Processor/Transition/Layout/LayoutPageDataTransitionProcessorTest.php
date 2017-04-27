<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutPageResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Layout\LayoutPageDataTransitionProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class LayoutPageDataTransitionProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutPageDataTransitionProcessor */
    private $processor;

    protected function setUp()
    {
        $this->processor = new LayoutPageDataTransitionProcessor();
    }

    public function testData()
    {
        /** @var WorkflowItem|\PHPUnit_Framework_MockObject_MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('workflowName');

        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('get')->with('originalUrl', '/')->willReturn('///url');

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $formView */
        $formView = $this->createMock(FormView::class);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('createView')->willReturn($formView);

        $context = new TransitionContext();
        $context->setWorkflowName('workflowName');
        $context->setTransitionName('transitionName');
        $context->setWorkflowItem($workflowItem);
        $context->setTransition($transition);
        $context->setRequest($request);
        $context->setForm($form);
        $context->setResultType(new LayoutPageResultType('route_name'));

        $this->processor->process($context);

        $this->assertSame(
            [
                'workflowName' => 'workflowName',
                'transitionName' => 'transitionName',
                'data' => [
                    'transitionFormView' => $formView,
                    'transition' => $transition,
                    'workflowItem' => $workflowItem,
                    'formRouteName' => 'route_name',
                    'originalUrl' => '///url',
                ]
            ],
            $context->getResult()
        );

        $this->assertTrue($context->isProcessed());
    }

    public function testSkipByUnsupportedResultType()
    {
        /** @var TransitionContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())->method('getWorkflowItem');

        $this->processor->process($context);
    }
}
