<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\SetFormData;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SetFormDataTest extends FormProcessorTestCase
{
    /** @var SetFormData */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->processor = new SetFormData();
    }

    public function testProcessWithoutEntity()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWithoutForm()
    {
        $entity = new \stdClass();

        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $entity = new \stdClass();
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($entity));

        $this->context->setResult($entity);
        $this->context->setForm($form);
        $this->processor->process($this->context);
    }
}
