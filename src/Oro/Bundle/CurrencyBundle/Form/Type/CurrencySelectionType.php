<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

class CurrencySelectionType extends AbstractCurrencySelectionType
{
    const NAME = 'oro_currency_selection';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
