<?php

namespace Oro\Bundle\CurrencyBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;

class PriceTransformer implements DataTransformerInterface
{
    /**
     * @param Price|null $price
     * @return Price|null
     */
    public function transform($price)
    {
        return $price;
    }

    /**
     * @param Price|null $price
     * @return Price|null
     */
    public function reverseTransform($price)
    {
        if (!$price || !$price instanceof Price || filter_var($price->getValue(), FILTER_VALIDATE_FLOAT) === false) {
            return null;
        }

        return $price;
    }
}
