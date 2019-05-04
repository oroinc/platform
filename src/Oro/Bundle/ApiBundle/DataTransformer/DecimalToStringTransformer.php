<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Brick\Math\BigDecimal;
use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Transforms a decimal value to a string.
 * Leading and tailing zeros and + sign are removed.
 */
class DecimalToStringTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value, array $config, array $context)
    {
        if (null === $value) {
            return $value;
        }

        $result = (string)BigDecimal::of($value);
        if (false !== \strpos($result, '.')) {
            $result = \rtrim(\rtrim($result, '0'), '.');
        }

        return $result;
    }
}
