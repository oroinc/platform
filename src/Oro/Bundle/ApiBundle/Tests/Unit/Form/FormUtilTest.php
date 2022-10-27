<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGranted;
use Oro\Bundle\ApiBundle\Validator\Constraints\All;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validation;

class FormUtilTest extends \PHPUnit\Framework\TestCase
{
    private function createFormBuilder(): FormBuilder
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();

        return new FormBuilder(
            null,
            null,
            $this->createMock(EventDispatcherInterface::class),
            $formFactory
        );
    }

    public function testFixValidationErrorPropertyPathForExpandedProperty(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => Product::class])
            ->add('currency', TextType::class, ['mapped' => false])
            ->add('value', TextType::class, ['mapped' => false])
            ->getForm();
        $form->addError(new FormError(
            'some error 1',
            null,
            [],
            null,
            new ConstraintViolation('', '', [], null, 'data.price.value', '')
        ));
        $form->addError(new FormError(
            'some error 2',
            null,
            [],
            null,
            new ConstraintViolation('', '', [], null, 'data.other', '')
        ));

        FormUtil::fixValidationErrorPropertyPathForExpandedProperty($form, 'price');

        self::assertEquals(
            ['data.value', 'data.other'],
            array_map(
                function (FormError $error): string {
                    return $error->getCause()->getPropertyPath();
                },
                iterator_to_array($form->getErrors())
            )
        );
    }

    public function testRemoveAccessGrantedValidationConstraint(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => Product::class])
            ->add('category', TextType::class, ['constraints' => [new AccessGranted(), new NotNull()]])
            ->getForm();

        FormUtil::removeAccessGrantedValidationConstraint($form, 'category');

        self::assertEquals(
            [new NotNull()],
            $form->get('category')->getConfig()->getOption('constraints')
        );
    }

    public function testRemoveAccessGrantedValidationConstraintWhenItIsWrappedWithAllConstraint(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => Product::class])
            ->add('category', TextType::class, ['constraints' => [new All(new AccessGranted()), new NotNull()]])
            ->getForm();

        FormUtil::removeAccessGrantedValidationConstraint($form, 'category');

        self::assertEquals(
            [new NotNull()],
            $form->get('category')->getConfig()->getOption('constraints')
        );
    }

    public function testRemoveAccessGrantedValidationConstraintForRenamedField(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => Product::class])
            ->add(
                'renamedCategory',
                TextType::class,
                ['property_path' => 'category', 'constraints' => [new AccessGranted(), new NotNull()]]
            )
            ->getForm();

        FormUtil::removeAccessGrantedValidationConstraint($form, 'category');

        self::assertEquals(
            [new NotNull()],
            $form->get('renamedCategory')->getConfig()->getOption('constraints')
        );
    }

    public function testRemoveAccessGrantedValidationConstraintWhenItDoesNotExists(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => Product::class])
            ->add('category', TextType::class, ['constraints' => [new NotNull()]])
            ->getForm();

        FormUtil::removeAccessGrantedValidationConstraint($form, 'category');

        self::assertEquals(
            [new NotNull()],
            $form->get('category')->getConfig()->getOption('constraints')
        );
    }

    public function testRemoveAccessGrantedValidationConstraintWhenNoConstraints(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => Product::class])
            ->add('category', TextType::class)
            ->getForm();

        FormUtil::removeAccessGrantedValidationConstraint($form, 'category');

        self::assertEquals(
            [],
            $form->get('category')->getConfig()->getOption('constraints')
        );
    }

    public function testRemoveAccessGrantedValidationConstraintWhenNoField(): void
    {
        $form = $this->createFormBuilder()
            ->create('testForm', null, ['compound' => true, 'data_class' => Product::class])
            ->add('owner', TextType::class)
            ->getForm();

        FormUtil::removeAccessGrantedValidationConstraint($form, 'category');
    }
}
