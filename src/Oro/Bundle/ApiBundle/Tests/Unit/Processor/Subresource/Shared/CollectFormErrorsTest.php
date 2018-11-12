<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\CollectFormErrors;
use Oro\Bundle\ApiBundle\Request\ConstraintTextExtractor;
use Oro\Bundle\ApiBundle\Request\ErrorCompleterRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints;

class CollectFormErrorsTest extends ChangeRelationshipProcessorTestCase
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

    public function testErrorPropertyPathShouldBeEmptyStringForToOneAssociationRelatedError()
    {
        $associationName = 'testAssociation';

        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add($associationName, TextType::class, ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit([$associationName => null]);

        $this->context->setAssociationName($associationName);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
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
                CollectionType::class,
                [
                    'entry_type'      => TextType::class,
                    'entry_options'   => ['constraints' => [new Constraints\NotBlank()]],
                    'allow_add' => true
                ]
            )
            ->getForm();
        $form->submit([$associationName => ['val1', null, 'val3']]);

        $this->context->setAssociationName($associationName);
        $this->context->setForm($form);
        $this->processor->process($this->context);

        self::assertFalse($form->isValid());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [$this->createErrorObject('not blank constraint', 'This value should not be blank.', '1')],
            $this->context->getErrors()
        );
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
