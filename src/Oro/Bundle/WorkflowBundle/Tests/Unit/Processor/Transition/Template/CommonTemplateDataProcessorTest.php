<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition\Template;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitActionResultTypeInterface;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\Template\CommonTemplateDataProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CommonTemplateDataProcessorTest extends TestCase
{
    private CommonTemplateDataProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->processor = new CommonTemplateDataProcessor();
    }

    public function testDataProcessing(): void
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

        $errorsIterator = new FormErrorIterator(
            $form,
            [
                new FormError('error1'),
                new FormError('error2')
            ]
        );
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);
        $form->expects($this->once())
            ->method('getErrors')
            ->with(true)
            ->willReturn($errorsIterator);

        $this->processor->process($context);

        $this->assertSame(
            [
                'transition' => $transition,
                'workflowItem' => $workflowItem,
                'saved' => false,
                'form' => $formView,
                'formErrors' => $errorsIterator
            ],
            $context->get('template_parameters')
        );
    }

    public function testSkipNonTemplateResponseTypeContext(): void
    {
        $context = $this->createMock(TransitionContext::class);
        $context->expects($this->once())
            ->method('getResultType')
            ->willReturn($this->createMock(TransitActionResultTypeInterface::class));

        $context->expects($this->never())
            ->method('getForm');

        $this->processor->process($context);
    }

    public function testSkipSavedContext(): void
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
