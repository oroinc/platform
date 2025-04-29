<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Form\Type\ArrayType;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\CollectFormErrors;
use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType\NameValuePairType;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormValidation\TestObject;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CollectFormErrorsTest extends FormProcessorTestCase
{
    private ErrorCompleterRegistry&MockObject $errorCompleterRegistry;
    private TranslatorInterface&MockObject $translator;
    private CollectFormErrors $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->errorCompleterRegistry = $this->createMock(ErrorCompleterRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->processor = new CollectFormErrors(
            new ConstraintTextExtractor(),
            $this->errorCompleterRegistry,
            PropertyAccess::createPropertyAccessor()
        );
        $this->processor->setTranslator($this->translator);
    }

    private function createErrorObject(string $title, string $detail, string $propertyPath): Error
    {
        $error = Error::createValidationError($title, $detail);
        if ($propertyPath) {
            $error->setSource(ErrorSource::createByPropertyPath($propertyPath));
        }

        return $error;
    }

    public function testProcessWithoutForm(): void
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithNotSubmittedForm(): void
    {
        $form = $this->createFormBuilder()->create('testForm')->getForm();

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithoutFormConstraints(): void
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

    public function testProcessWithEmptyData(): void
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->add('field2', TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit([]);

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1'),
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field2')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithDataKeyWhichDoesNotRegisteredInForm(): void
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
        self::assertTrue($form->isSynchronized());
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

    public function testProcessWithPropertyWhichDoesNotRegisteredInFormAndHasInvalidExistingValue(): void
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'title')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidRenamedPropertyValue(): void
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'renamedTitle')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidPropertyValues(): void
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class, ['constraints' => [new Constraints\NotBlank(), new Constraints\NotNull()]])
            ->add(
                'field2',
                TextType::class,
                ['constraints' => [new Constraints\Length(['min' => 2, 'max' => 4]), new Constraints\NotBlank()]]
            )
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
        self::assertTrue($form->isSynchronized());
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

    public function testProcessWithInvalidCollectionPropertyValue(): void
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', ArrayType::class, ['constraints' => [new Constraints\All(new Constraints\NotNull())]])
            ->getForm();
        $form->submit(
            [
                'field1' => [1, null]
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not null constraint', 'This value should not be null.', 'field1.1')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidCollectionRenamedPropertyValue(): void
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add(
                'renamedField1',
                ArrayType::class,
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not null constraint', 'This value should not be null.', 'renamedField1.1')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidCollectionPropertyValueWhenFormFieldIsCollectionType(): void
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1.1')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidCollectionRenamedPropertyValueWhenFormFieldIsCollectionType(): void
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'renamedField1.1')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidValueOfThirdNestedLevelProperty(): void
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1.0.name')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidValueOfThirdNestedLevelPropertyAndEnabledErrorBubbling(): void
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1.0.name')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithRootLevelValidationErrorOccurred(): void
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('callback constraint', 'Some issue with a whole form data', 'testForm')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithCustomErrorOccurred(): void
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('form constraint', 'custom error', 'field1.0.name')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenValidationErrorWasAddedViaFormUtilAddFormConstraintViolation(): void
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class)
            ->getForm();
        $form->submit([]);

        FormUtil::addFormConstraintViolation(
            $form->get('field1'),
            new Constraints\NotBlank()
        );

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnCallback(static function ($key) {
                return '[TRANSLATED] ' . $key;
            });

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject(
                    'not blank constraint',
                    '[TRANSLATED] This value should not be blank.',
                    'field1'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenValidationErrorWasAddedViaFormUtilAddFormConstraintViolationAndCustomMessage(): void
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', TextType::class)
            ->getForm();
        $form->submit([]);

        FormUtil::addFormConstraintViolation(
            $form->get('field1'),
            new Constraints\NotBlank(),
            'Some custom message.'
        );

        $this->translator->expects(self::never())
            ->method('trans');

        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'Some custom message.', 'field1')
            ],
            $this->context->getErrors()
        );
    }
}
