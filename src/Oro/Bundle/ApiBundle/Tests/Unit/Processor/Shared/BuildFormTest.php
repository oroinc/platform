<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\BuildForm;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class BuildFormTest extends FormProcessorTestCase
{
    /** @var BuildForm */
    protected $processor;

    public function setUp()
    {
        parent::setUp();
        $this->processor = new BuildForm();
    }

    public function testProcessWhenFormAlreadyExists()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->context->setForm($form);
        $this->processor->process($this->context);
        $this->assertSame($form, $this->context->getForm());
    }

    public function testProcessWhenFormBuilderDoesNotExists()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasForm());
    }

    public function testProcess()
    {
        $formBuilder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $formBuilder->expects($this->once())
            ->method('getForm')
            ->willReturn($form);

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);
        $this->assertSame($form, $this->context->getForm());
        $this->assertFalse($this->context->hasFormBuilder());
    }
}
