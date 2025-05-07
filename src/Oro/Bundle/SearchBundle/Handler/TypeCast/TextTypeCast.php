<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'text' type.
 */
class TextTypeCast implements TypeCastingHandlerInterface
{
    #[\Override]
    public function castValue(mixed $value): mixed
    {
        if (\is_string($value) || (\is_object($value) && method_exists($value, '__toString'))) {
            return trim($value);
        }

        if (\is_scalar($value) || $value instanceof \DateTime) {
            throw new TypeCastingException(Query::TYPE_TEXT);
        }

        return $value;
    }
}
