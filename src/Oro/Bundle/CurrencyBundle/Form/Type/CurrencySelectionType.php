<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

class CurrencySelectionType extends AbstractCurrencySelectionType
{
    const NAME = 'oro_currency_selection';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
