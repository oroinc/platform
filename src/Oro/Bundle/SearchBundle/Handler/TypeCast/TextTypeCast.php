<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'integer' type.
 */
class TextTypeCast extends AbstractTypeCastingHandler
{
    /**
     * @param mixed $value
     *
     * @return object|string
     */
    public function castValue($value)
    {
        if ($this->isSupported($value)) {
            return trim($value);
        }

        return parent::castValue($value);
    }

    public function isSupported($value): bool
    {
        return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
    }

    public static function getType(): string
    {
        return Query::TYPE_TEXT;
    }
}
