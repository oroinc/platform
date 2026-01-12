<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

/**
 * Form type for selecting currencies from the system's configured currency list.
 *
 * This form type extends the base currency selection functionality to provide
 * a standard currency selector that uses the application's configured currencies.
 * It can be used in forms where users need to choose a currency from the available
 * options defined in the system configuration.
 */
class CurrencySelectionType extends AbstractCurrencySelectionType
{
    public const NAME = 'oro_currency_selection';

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
