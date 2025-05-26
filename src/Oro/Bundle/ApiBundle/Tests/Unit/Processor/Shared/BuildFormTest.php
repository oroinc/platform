<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\BuildForm;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class BuildFormTest extends FormProcessorTestCase
{
    private BuildForm $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new BuildForm();
    }

    public function testProcessWhenFormAlreadyExists(): void
    {
        $form = $this->createMock(FormInterface::class);

        $this->context->setForm($form);
        $this->processor->process($this->context);
        self::assertSame($form, $this->context->getForm());
    }

    public function testProcessWhenFormBuilderDoesNotExists(): void
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasForm());
    }

    public function testProcess(): void
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);

        $formBuilder->expects(self::once())
            ->method('getForm')
            ->willReturn($form);

        $this->context->setFormBuilder($formBuilder);
        $this->processor->process($this->context);
        self::assertSame($form, $this->context->getForm());
        self::assertFalse($this->context->hasFormBuilder());
    }
}
