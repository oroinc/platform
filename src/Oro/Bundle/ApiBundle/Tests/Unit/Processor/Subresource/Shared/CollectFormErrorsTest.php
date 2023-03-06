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
    private CollectFormErrors $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new CollectFormErrors(
            new ConstraintTextExtractor(),
            $this->createMock(ErrorCompleterRegistry::class),
            PropertyAccess::createPropertyAccessor()
        );
    }

    private function createErrorObject(string $title, string $detail, string $propertyPath = null): Error
    {
        $error = Error::createValidationError($title, $detail);
        if (null !== $propertyPath) {
            $error->setSource(ErrorSource::createByPropertyPath($propertyPath));
        }

        return $error;
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
        self::assertTrue($form->isSynchronized());
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
        self::assertTrue($form->isSynchronized());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [$this->createErrorObject('not blank constraint', 'This value should not be blank.', '1')],
            $this->context->getErrors()
        );
    }
}
