<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Stub;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFormTypeExtensionStub implements FormTypeExtensionInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
    }

    public static function createUniqueInstance(string $extendedType): AbstractFormTypeExtensionStub
    {
        do {
            $extensionClass = sprintf(
                '%sExtensionStub%s',
                ucfirst($extendedType),
                bin2hex(random_bytes(10))
            );
        } while (class_exists($extensionClass));

        eval(<<<EOF
            class $extensionClass extends \Oro\Bundle\ApiBundle\Tests\Unit\Stub\AbstractFormTypeExtensionStub {
                public static function getExtendedTypes(): iterable
                {
                    return ['$extendedType'];
                }
            };
        EOF);

        return new $extensionClass;
    }
}
