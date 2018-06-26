<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutPageResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Layout\LayoutPageDataTransitionProcessor;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class LayoutPageDataTransitionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $helper;

    /** @var LayoutPageDataTransitionProcessor */
    private $processor;

    protected function setUp()
    {
        $this->helper = $this->createMock(TransitionTranslationHelper::class);

        $this->processor = new LayoutPageDataTransitionProcessor($this->helper);
    }

    public function testData()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('workflowName');

        /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow */
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('getLabel')->willReturn('workflow.label');

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('get')->with('originalUrl', '/')->willReturn('///url');

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->createMock(FormView::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('createView')->willReturn($formView);

        $context = new TransitionContext();
        $context->setWorkflowName('workflowName');
        $context->setTransitionName('transitionName');
        $context->setWorkflowItem($workflowItem);
        $context->setTransition($transition);
        $context->setWorkflow($workflow);
        $context->setRequest($request);
        $context->setForm($form);
        $context->setResultType(new LayoutPageResultType('route_name'));

        $this->helper->expects($this->once())->method('processTransitionTranslations')->with($transition);

        $this->processor->process($context);

        $this->assertSame(
            [
                'workflowName' => 'workflow.label',
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
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())->method('getWorkflowItem');

        $this->processor->process($context);
    }
}
