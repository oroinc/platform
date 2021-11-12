<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Layout;

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
    private $helper;

    /** @var LayoutPageDataStartTransitionProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(TransitionTranslationHelper::class);

        $this->processor = new LayoutPageDataStartTransitionProcessor($this->helper);
    }

    public function testResultData()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getLabel')
            ->willReturn('workflow.label');

        $transition = $this->createMock(Transition::class);
        $request = new Request(['entityId' => 42, 'originalUrl' => '///url']);

        $formView = $this->createMock(FormView::class);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $context = new TransitionContext();
        $context->setWorkflowItem($workflowItem);
        $context->setWorkflowName('workflowName');
        $context->setTransition($transition);
        $context->setWorkflow($workflow);
        $context->setTransitionName('transitionName');
        $context->setRequest($request);
        $context->setForm($form);
        $context->setResultType(new LayoutPageResultType('route_name'));

        $this->helper->expects($this->once())
            ->method('processTransitionTranslations')
            ->with($transition);

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
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())
            ->method('getWorkflowItem');

        $this->processor->process($context);
    }
}
