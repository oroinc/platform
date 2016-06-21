<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\CollectFormErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipContextTest;

class CollectFormErrorsTest extends ChangeRelationshipContextTest
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

    public function testErrorPropertyPathShouldBeEmptyStringForToOneAssociationRelatedError()
    {
        $associationName = 'testAssociation';

        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add($associationName, 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit([$associationName => null]);

        $this->context->setAssociationName($associationName);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
            [$this->createErrorObject('not blank constraint', 'This value should not be blank.', '')],
            $this->context->getErrors()
        );
    }

    public function testAssociationNameShouldBeRemovedFromErrorPropertyPathForToManyAssociationRelatedError()
    {
        $associationName = 'testAssociation';

        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add(
                $associationName,
                'collection',
                [
                    'type'      => 'text',
                    'options'   => ['constraints' => [new Constraints\NotBlank()]],
                    'allow_add' => true
                ]
            )
            ->getForm();
        $form->submit([$associationName => ['val1', null, 'val3']]);

        $this->context->setAssociationName($associationName);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
            [$this->createErrorObject('not blank constraint', 'This value should not be blank.', '1')],
            $this->context->getErrors()
        );
    }

    /**
     * @return FormBuilder
     */
    protected function createFormBuilder()
    {
        $validator = Validation::createValidatorBuilder()->getValidator();
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions([new ValidatorExtension($validator)])
            ->getFormFactory();
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        return new FormBuilder(null, null, $dispatcher, $formFactory);
    }

    /**
     * @param string      $title
     * @param string      $detail
     * @param string|null $propertyPath
     *
     * @return Error
     */
    protected function createErrorObject($title, $detail, $propertyPath = null)
    {
        $error = Error::createValidationError($title, $detail);
        if (null !== $propertyPath) {
            $error->setSource(ErrorSource::createByPropertyPath($propertyPath));
        }

        return $error;
    }
}
