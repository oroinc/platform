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
     * {@inheritDoc}
     */
    public function transform(mixed $value, array $config, array $context): mixed
    {
        if (null === $value) {
            return null;
        }

        $result = (string)BigDecimal::of($value);
        if (str_contains($result, '.')) {
            $result = rtrim(rtrim($result, '0'), '.');
        }

        return $result;
    }
}
