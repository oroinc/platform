<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'decimal' type.
 */
class DecimalTypeCast extends AbstractTypeCastingHandler
{
    /**
     * @param mixed $value
     *
     * @return object|float
     */
    public function castValue($value)
    {
        if ($this->isSupported($value)) {
            return (float)$value;
        }

        return parent::castValue($value);
    }

    public function isSupported($value): bool
    {
        return is_double($value) || is_int($value);
    }

    public static function getType(): string
    {
        return Query::TYPE_DECIMAL;
    }
}
