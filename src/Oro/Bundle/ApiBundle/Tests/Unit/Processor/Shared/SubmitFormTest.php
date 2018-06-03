<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\SubmitForm;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Symfony\Component\Form\FormInterface;

class SubmitFormTest extends FormProcessorTestCase
{
    /** @var SubmitForm */
    private $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->processor = new SubmitForm();
    }

    public function testProcessWithoutForm()
    {
        $entity = new \stdClass();

        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForAlreadySubmittedForm()
    {
        $form = $this->createMock(FormInterface::class);

        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(true);
        $form->expects(self::never())
            ->method('submit');

        $this->context->setForm($form);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $requestData = [
            'field1' => 123,
            'field2' => false,
            'field3' => [
                'key1' => false
            ]
        ];
        $expectedRequestData = [
            'field1' => 123,
            'field2' => 'false',
            'field3' => [
                'key1' => 'false'
            ]
        ];

        $form = $this->createMock(FormInterface::class);

        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);
        $form->expects(self::once())
            ->method('submit')
            ->with($expectedRequestData, false);

        $this->context->setRequestData($requestData);
        $this->context->setForm($form);
        $this->processor->process($this->context);
    }
}
