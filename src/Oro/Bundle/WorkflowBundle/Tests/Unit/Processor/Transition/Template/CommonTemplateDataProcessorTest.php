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
    /** @var CommonTemplateDataProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new CommonTemplateDataProcessor();
    }

    public function testDataProcessing()
    {
        $form = $this->createMock(FormInterface::class);

        $formView = $this->createMock(FormView::class);

        $transition = $this->createMock(Transition::class);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('test_workflow');

        $context = new TransitionContext();
        $context->setForm($form);
        $context->setTransition($transition);
        $context->setWorkflowItem($workflowItem);
        $context->setResultType(new TemplateResultType());

        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->once())
            ->method('getErrors')
            ->with(true)
            ->willReturn(['error1', 'error2']);

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
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())
            ->method('getForm');

        $this->processor->process($context);
    }

    public function testSkipSavedContext()
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn(new TemplateResultType());

        $context->expects($this->once())
            ->method('isSaved')
            ->willReturn(true);
        $context->expects($this->never())
            ->method('getForm');

        $this->processor->process($context);
    }
}
