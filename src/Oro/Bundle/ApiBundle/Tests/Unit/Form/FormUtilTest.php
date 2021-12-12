<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\ConstraintViolation;

class FormUtilTest extends \PHPUnit\Framework\TestCase
{
    private function createFormBuilder(): FormBuilder
    {
        return new FormBuilder(
            null,
            null,
            $this->createMock(EventDispatcherInterface::class),
            Forms::createFormFactoryBuilder()->getFormFactory()
        );
    }

    public function testPostValidate()
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
}
