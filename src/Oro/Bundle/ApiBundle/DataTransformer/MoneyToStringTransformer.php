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
     * {@inheritDoc}
     */
    public function transform(mixed $value, array $config, array $context): mixed
    {
        if (null === $value) {
            return null;
        }

        return (string)BigDecimal::of($value)->toScale(MoneyType::TYPE_SCALE, RoundingMode::DOWN);
    }
}
