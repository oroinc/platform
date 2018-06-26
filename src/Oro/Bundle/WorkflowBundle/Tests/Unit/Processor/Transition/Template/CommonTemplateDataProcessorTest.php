<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Template;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Template\CommonTemplateDataProcessor;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CommonTemplateDataProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var  CommonTemplateDataProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->processor = new CommonTemplateDataProcessor();
    }

    public function testDataProcessing()
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);

        /** @var FormView|\PHPUnit\Framework\MockObject\MockObject $formView */
        $formView = $this->createMock(FormView::class);

        /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
        $transition = $this->createMock(Transition::class);

        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn('test_workflow');

        $context = new TransitionContext();
        $context->setForm($form);
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);
        $context->setResultType(new TemplateResultType());

        $form->expects($this->once())->method('createView')->willReturn($formView);
        $form->expects($this->once())->method('getErrors')->with(true)->willReturn(['error1', 'error2']);

        $this->processor->process($context);

        $this->assertSame(
            [
                'transition' => $transition,
                'workflowItem' => $workflowItem,
                'saved' => false,
                'form' => $formView,
                'formErrors' => ['error1', 'error2']
            ],
            $context->get('template_parameters')
        );
    }

    public function testSkipNonTemplateResponseTypeContext()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())->method('getForm');

        $this->processor->process($context);
    }

    public function testSkipSavedContext()
    {
        /** @var TransitionContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn(new TemplateResultType());

        $context->expects($this->once())->method('isSaved')->willReturn(true);
        $context->expects($this->never())->method('getForm');

        $this->processor->process($context);
    }
}
