<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Tests\Unit\Transition\Layout;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Processor\Context\LayoutPageResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Layout\LayoutPageDataStartTransitionProcessor;
use Oro\Bundle\WorkflowBundle\Translation\Helper\TransitionTranslationHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class LayoutPageDataStartTransitionProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $helper;

    /** @var LayoutPageDataStartTransitionProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->helper = $this->createMock(TransitionTranslationHelper::class);

        $this->processor = new LayoutPageDataStartTransitionProcessor($this->helper);
    }

    public function testResultData()
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');

        /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow */
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('getLabel')->willReturn('workflow.label');

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $request = new Request(['entityId' => 42, 'originalUrl' => '///url']);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->createMock(FormView::class);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('createView')->willReturn($formView);

        $context = new TransitionContext();
        $context->setWorkflowItem($workflowItem);
        $context->setWorkflowName('workflowName');
        $context->setTransition($transition);
        $context->setWorkflow($workflow);
        $context->setTransitionName('transitionName');
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
                    'workflowName' => 'workflowName',
                    'workflowItem' => $workflowItem,
                    'transitionName' => 'transitionName',
                    'transition' => $transition,
                    'entityId' => 42,
                    'originalUrl' => '///url',
                    'formRouteName' => 'route_name',
                ]
            ],
            $context->getResult()
        );
        $this->assertTrue($context->isProcessed());
    }

    public function testSkipByResultType()
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
