<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\FormFactoryProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class FormFactoryProcessorTest extends TestCase
{
    private TransitionContext&MockObject $context;
    private FormFactoryInterface&MockObject $formFactory;
    private FormFactoryProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->context = $this->createMock(TransitionContext::class);

        $this->processor = new FormFactoryProcessor($this->formFactory);
    }

    public function testCreateForm(): void
    {
        $formData = (object)['id' => 42];

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFormType')
            ->willReturn('FormTypeFQCN');

        $form = $this->createMock(FormInterface::class);

        $this->context->expects($this->once())
            ->method('getFormData')
            ->willReturn($formData);
        $this->context->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $this->context->expects($this->once())
            ->method('getFormOptions')
            ->willReturn(['option' => 'option-val']);
        $this->context->expects($this->once())
            ->method('setForm')
            ->with($form);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with('FormTypeFQCN', $formData, ['option' => 'option-val'])
            ->willReturn($form);

        $this->processor->process($this->context);
    }

    public function testNoFormDataException(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Data for transition form is not defined');

        $this->context->expects($this->once())
            ->method('getFormData')
            ->willReturn(null);
        $this->context->expects($this->never())
            ->method('getTransition');

        $this->formFactory->expects($this->never())
            ->method('create');

        $this->processor->process($this->context);
    }
}
