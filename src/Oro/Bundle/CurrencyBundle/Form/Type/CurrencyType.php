<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType as SymfonyCurrencyType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for currency input fields.
 *
 * This form type extends Symfony's CurrencyType to provide currency selection
 * functionality with additional options specific to the Oro platform. It supports
 * an optional 'restrict' option to control currency filtering behavior.
 */
class CurrencyType extends AbstractType
{
    public const CONFIG_FORM_NAME = 'oro_currency___default_currency';

    #[\Override]
    public function getParent(): ?string
    {
        return SymfonyCurrencyType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_currency';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(['restrict' => false]);
    }
}
