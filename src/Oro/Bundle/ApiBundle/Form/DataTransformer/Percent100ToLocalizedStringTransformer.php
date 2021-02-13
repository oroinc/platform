<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Symfony\Component\Form\Extension\Core\DataTransformer\NumberToLocalizedStringTransformer;

/**
 * Transforms a value between a percentage value multiplied by 100 and a string.
 */
class Percent100ToLocalizedStringTransformer extends NumberToLocalizedStringTransformer
{
    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        return parent::transform(null !== $value && is_numeric($value) ? $value / 100.0 : $value);
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        $result = parent::reverseTransform($value);
        if (null !== $result) {
            $result *= 100.0;
        }

        return $result;
    }
}
