<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Brick\Math\BigDecimal;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'decimal' type.
 */
class DecimalTypeCast extends AbstractTypeCastingHandler
{
    public function castValue(mixed $value): mixed
    {
        if ($this->isSupported($value)) {
            return BigDecimal::of($value)->toFloat();
        }

        return parent::castValue($value);
    }

    public function isSupported($value): bool
    {
        return is_numeric($value);
    }

    public static function getType(): string
    {
        return Query::TYPE_DECIMAL;
    }
}
