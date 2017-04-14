<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Processor\Transition\FormFactoryProcessor;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class FormFactoryProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TransitionContext|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var FormFactoryProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->context = $this->createMock(TransitionContext::class);

        $this->processor = new FormFactoryProcessor($this->formFactory);
    }

    public function testCreateForm()
    {
        $formData = (object)['id' => 42];

        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())->method('getFormType')->willReturn('FormTypeFQCN');

        $form = $this->createMock(FormInterface::class);

        $this->context->expects($this->once())->method('getFormData')->willReturn($formData);
        $this->context->expects($this->once())->method('getTransition')->willReturn($transition);
        $this->context->expects($this->once())->method('getFormOptions')->willReturn(['option' => 'option-val']);
        $this->context->expects($this->once())->method('setForm')->with($form);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                'FormTypeFQCN',
                $formData,
                ['option' => 'option-val']
            )->willReturn($form);

        $this->processor->process($this->context);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Data for transition form is not defined
     */
    public function testNoFormDataException()
    {
        $this->context->expects($this->once())->method('getFormData')->willReturn(null);
        $this->context->expects($this->never())->method('getTransition');

        $this->formFactory->expects($this->never())->method('create');

        $this->processor->process($this->context);
    }
}
