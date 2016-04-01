<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\CollectFormErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormContextTestCase;

class CollectFormErrorsTest extends FormContextTestCase
{
    /** @var CollectFormErrors */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->processor = new CollectFormErrors();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The form must be set in the context.
     */
    public function testProcessWithoutForm()
    {
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The form must be submitted.
     */
    public function testWithNotSubmitterForm()
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions([])
            ->getFormFactory();
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $builder = new FormBuilder(null, null, $dispatcher, $formFactory);

        $form = $builder->create('testForm', null, [])->getForm();

        $this->context->setForm($form);

        $this->processor->process($this->context);
    }

    public function testWithValidForm()
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions([])
            ->getFormFactory();
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $builder = new FormBuilder(null, null, $dispatcher, $formFactory);

        $form = $builder->create('testForm', null, ['compound' => true])
            ->add('name', 'text')
            ->add('description', 'text')
            ->getForm();

        $form->submit([]);

        $this->context->setForm($form);

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testWithConstraints($data = [], $constraints = [], $errors = 0)
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions([
                new ValidatorExtension(Validation::createValidator())
            ])
            ->getFormFactory();
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $builder = new FormBuilder(null, null, $dispatcher, $formFactory);

        $form = $builder->create('test', null, ['compound' => true])
            ->add('field1', 'text', [
                'constraints' => $constraints[0]
            ])
            ->add('field2', 'text', [
                'constraints' => $constraints[1]
            ])
            ->getForm();

        $form->submit($data);

        $this->context->setForm($form);

        $this->processor->process($this->context);

        if ($errors) {
            $this->assertFalse($form->isValid());
            $this->assertTrue($this->context->hasErrors());
        }
        $this->assertCount($errors, $this->context->getErrors());
    }

    public function dataProvider()
    {
        return [
            'no_constraints' => [
                'data' => [],
                'constraints' => [
                    [],
                    []
                ],
                'errors' => 0
            ],
            'empty_data' => [
                'data' => [],
                'constraints' => [
                    [
                        new NotBlank()
                    ],
                    [
                        new NotBlank()
                    ]
                ],
                'errors' => 2 // both fields empty
            ],
            'invalid_data_key' => [
                'data' => [
                    'field1' => 'value',
                    'field3' => 'value'
                ],
                'constraints' => [
                    [
                        new NotBlank()
                    ],
                    [
                        new NotBlank(),
                    ]
                ],
                'errors' => 2 // one field empty, extra data
            ],
            'invalid_values' => [
                'data' => [
                    'field1' => null,
                    'field2' => 'value'
                ],
                'constraints' => [
                    [
                        new NotBlank(),
                        new NotNull()
                    ],
                    [
                        new Length(['min' => 2, 'max' => 4])
                    ]
                ],
                'errors' => 3
            ]
        ];
    }
}
