<?php

namespace Oro\Bundle\CurrencyBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;

class MultiCurrencyTransformer implements DataTransformerInterface
{
    /**
     * @param MultiCurrency|null $multiCurrency
     * @return MultiCurrency|null
     */
    public function transform($multiCurrency)
    {
        return $multiCurrency;
    }

    /**
     * @param MultiCurrency|null $multiCurrency
     * @return MultiCurrency|null
     */
    public function reverseTransform($multiCurrency)
    {
        if (!$multiCurrency
            || !$multiCurrency instanceof MultiCurrency
            || filter_var($multiCurrency->getValue(), FILTER_VALIDATE_FLOAT) === false
        ) {
            return null;
        }

        return $multiCurrency;
    }
}
