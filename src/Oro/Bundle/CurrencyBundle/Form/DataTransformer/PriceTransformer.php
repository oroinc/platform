<?php

namespace Oro\Bundle\CurrencyBundle\Form\DataTransformer;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms and validates a Price entity in form data.
 */
class PriceTransformer implements DataTransformerInterface
{
    /**
     * @param Price|null $price
     * @return Price|null
     */
    #[\Override]
    public function transform($price): mixed
    {
        return $price;
    }

    /**
     * @param Price|null $price
     * @return Price|null
     */
    #[\Override]
    public function reverseTransform($price): mixed
    {
        if (!$price || !$price instanceof Price || filter_var($price->getValue(), FILTER_VALIDATE_FLOAT) === false) {
            return null;
        }

        return $price;
    }
}
