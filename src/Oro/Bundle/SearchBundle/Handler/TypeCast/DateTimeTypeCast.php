<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'DateTime' type.
 */
class DateTimeTypeCast extends AbstractTypeCastingHandler
{
    /**
     * @param mixed $value
     *
     * @return object|\DateTime
     */
    public function castValue($value)
    {
        if ($this->isSupported($value)) {
            return $value;
        }

        return parent::castValue($value);
    }

    public function isSupported($value): bool
    {
        return $value instanceof \DateTime;
    }

    public static function getType(): string
    {
        return Query::TYPE_DATETIME;
    }
}
