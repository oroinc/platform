<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\CollectFormErrors;
use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\NameValuePairType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormValidation\TestObject;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CollectFormErrorsTest extends FormProcessorTestCase
{
    /** @var ErrorCompleterRegistry */
    private $errorCompleterRegistry;

    /** @var CollectFormErrors */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);

        $this->processor = new CollectFormErrors(
            new ConstraintTextExtractor(),
            $this->errorCompleterRegistry,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testProcessWithoutForm()
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithNotSubmittedForm()
    {
        $form = $this->createFormBuilder()->create('testForm')->getForm();

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithoutFormConstraints()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class)
            ->add('field2', TextType::class)
            ->getForm();
        $form->submit([]);

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithEmptyData()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('field2', TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit([]);

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1'),
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field2')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithDataKeyWhichDoesNotRegisteredInForm()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('field2', TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit(
            [
                'field1' => 'value',
                'field3' => 'value'
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject(
                    'extra fields constraint',
                    'This form should not contain extra fields.',
                    'testForm'
                ),
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field2')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithPropertyWhichDoesNotRegisteredInFormAndHasInvalidExistingValue()
    {
        $dataClass = TestObject::class;
        $data = new $dataClass();

        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true, 'data_class' => $dataClass])
            ->add('id', IntegerType::class)
            ->getForm();
        $form->setData($data);
        $form->submit(
            [
                'id' => 123
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'title')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidRenamedPropertyValue()
    {
        $dataClass = TestObject::class;
        $data = new $dataClass();

        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true, 'data_class' => $dataClass])
            ->add('renamedTitle', TextType::class, ['property_path' => 'title'])
            ->getForm();
        $form->setData($data);
        $form->submit(
            [
                'renamedTitle' => null
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'renamedTitle')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidPropertyValues()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class, ['constraints' => [new Constraints\NotBlank(), new Constraints\NotNull()]])
            ->add('field2', TextType::class, ['constraints' => [new Constraints\Length(['min' => 2, 'max' => 4])]])
            ->getForm();
        $form->submit(
            [
                'field1' => null,
                'field2' => 'value'
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1'),
                $this->createErrorObject('not null constraint', 'This value should not be null.', 'field1'),
                $this->createErrorObject(
                    'length constraint',
                    'This value is too long. It should have 4 characters or less.',
                    'field2'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidCollectionPropertyValue()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class, ['constraints' => [new Constraints\All(new Constraints\NotNull())]])
            ->getForm();
        $form->submit(
            [
                'field1' => [1, null]
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not null constraint', 'This value should not be null.', 'field1.1')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidCollectionRenamedPropertyValue()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add(
                'renamedField1',
                TextType::class,
                ['property_path' => '[field1]', 'constraints' => [new Constraints\All(new Constraints\NotNull())]]
            )
            ->getForm();
        $form->submit(
            [
                'renamedField1' => [1, null]
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not null constraint', 'This value should not be null.', 'renamedField1.1')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidCollectionPropertyValueWhenFormFieldIsCollectionType()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add(
                'field1',
                CollectionType::class,
                [
                    'entry_type'    => TextType::class,
                    'entry_options' => ['constraints' => [new Constraints\NotBlank()]],
                    'allow_add'     => true
                ]
            )
            ->getForm();
        $form->submit(
            [
                'field1' => [1, null]
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1.1')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidCollectionRenamedPropertyValueWhenFormFieldIsCollectionType()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add(
                'renamedField1',
                CollectionType::class,
                [
                    'property_path' => '[field1]',
                    'entry_type'    => TextType::class,
                    'entry_options' => ['constraints' => [new Constraints\NotBlank()]],
                    'allow_add'     => true
                ]
            )
            ->getForm();
        $form->submit(
            [
                'renamedField1' => [1, null]
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'renamedField1.1')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidValueOfThirdNestedLevelProperty()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add(
                'field1',
                CollectionType::class,
                [
                    'entry_type'    => NameValuePairType::class,
                    'entry_options' => [
                        'name_options' => ['constraints' => [new Constraints\NotBlank()]]
                    ],
                    'allow_add'     => true
                ]
            )
            ->getForm();
        $form->submit(
            [
                'field1' => [
                    [
                        'name' => null
                    ]
                ]
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1.0.name')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidValueOfThirdNestedLevelPropertyAndEnabledErrorBubbling()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add(
                'field1',
                CollectionType::class,
                [
                    'entry_type'    => NameValuePairType::class,
                    'entry_options' => [
                        'name_options' => [
                            'constraints'    => [new Constraints\NotBlank()],
                            'error_bubbling' => true
                        ]
                    ],
                    'allow_add'     => true
                ]
            )
            ->getForm();
        $form->submit(
            [
                'field1' => [
                    [
                        'name' => null
                    ]
                ]
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1.0.name')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithRootLevelValidationErrorOccurred()
    {
        $rootLevelConstraint = new Constraints\Callback(
            function ($object, ExecutionContextInterface $context) {
                $context->addViolation('Some issue with a whole form data');
            }
        );
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'constraints' => [$rootLevelConstraint]])
            ->add('field1', TextType::class)
            ->getForm();
        $form->submit(
            [
                'field1' => null
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('callback constraint', 'Some issue with a whole form data', 'testForm')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithCustomErrorOccurred()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add(
                'field1',
                CollectionType::class,
                [
                    'entry_type' => NameValuePairType::class,
                    'allow_add'  => true
                ]
            )
            ->getForm();
        $form->submit(
            [
                'field1' => [
                    [
                        'name' => 1
                    ]
                ]
            ]
        );
        $form->get('field1')->get('0')->get('name')->addError(new FormError('custom error'));

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('form constraint', 'custom error', 'field1.0.name')
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @param string $title
     * @param string $detail
     * @param string $propertyPath
     *
     * @return Error
     */
    private function createErrorObject($title, $detail, $propertyPath)
    {
        $error = Error::createValidationError($title, $detail);
        if ($propertyPath) {
            $error->setSource(ErrorSource::createByPropertyPath($propertyPath));
        }

        return $error;
    }
}
