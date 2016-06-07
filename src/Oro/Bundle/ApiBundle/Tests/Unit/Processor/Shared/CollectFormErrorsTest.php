<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validation;

use Doctrine\Common\Annotations\AnnotationReader;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\CollectFormErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class CollectFormErrorsTest extends FormProcessorTestCase
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

    public function testProcessWithoutForm()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithNotSubmittedForm()
    {
        $form = $this->createFormBuilder()->create('testForm')->getForm();

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithoutFormConstraints()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text')
            ->add('field2', 'text')
            ->getForm();
        $form->submit([]);

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithEmptyData()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->add('field2', 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit([]);

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field1'),
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'field2')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidDataKey()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->add('field2', 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit(
            [
                'field1' => 'value',
                'field3' => 'value'
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
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

    public function testProcessWithInvalidPropertyWhichDoesNotRegisteredInFormButHasValidationConstraint()
    {
        $dataClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormValidation\TestObject';
        $data = new $dataClass();

        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true, 'data_class' => $dataClass])
            ->add('id', 'integer')
            ->getForm();
        $form->setData($data);
        $form->submit(
            [
                'id' => 123,
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
            [
                $this->createErrorObject('not blank constraint', 'This value should not be blank.', 'title'),
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidValues()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text', ['constraints' => [new Constraints\NotBlank(), new Constraints\NotNull()]])
            ->add('field2', 'text', ['constraints' => [new Constraints\Length(['min' => 2, 'max' => 4])]])
            ->getForm();
        $form->submit(
            [
                'field1' => null,
                'field2' => 'value'
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
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

    public function testProcessWithInvalidCollectionValue()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text', ['constraints' => [new Constraints\All(new Constraints\NotNull())]])
            ->getForm();
        $form->submit(
            [
                'field1' => [1, null],
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
            [
                $this->createErrorObject('not null constraint', 'This value should not be null.', 'field1.1')
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @return FormBuilder
     */
    protected function createFormBuilder()
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping(new AnnotationReader())
            ->getValidator();
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions([new ValidatorExtension($validator)])
            ->getFormFactory();
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        return new FormBuilder(null, null, $dispatcher, $formFactory);
    }

    /**
     * @param string $title
     * @param string $detail
     * @param string $propertyPath
     *
     * @return Error
     */
    protected function createErrorObject($title, $detail, $propertyPath)
    {
        $error = Error::createValidationError($title, $detail);
        if ($propertyPath) {
            $error->setSource(ErrorSource::createByPropertyPath($propertyPath));
        }

        return $error;
    }
}
