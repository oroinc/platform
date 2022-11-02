<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Component\EntitySerializer\DataTransformerInterface;
use Oro\DBAL\Types\MoneyType;

/**
 * Transforms a money value to a string taking into account the scale of "money" DBAL type.
 */
class MoneyToStringTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value, array $config, array $context)
    {
        if (null === $value) {
            return $value;
        }

        return (string)BigDecimal::of($value)->toScale(MoneyType::TYPE_SCALE, RoundingMode::DOWN);
    }
}
