<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'integer' type.
 * Allows to convert type 'boolean' to type 'integer'.
 */
class IntegerTypeCast implements TypeCastingHandlerInterface
{
    #[\Override]
    public function castValue(mixed $value): mixed
    {
        if (\is_int($value) || \is_bool($value)) {
            return (int)$value;
        }

        if (\is_scalar($value) || $value instanceof \DateTime) {
            throw new TypeCastingException(Query::TYPE_INTEGER);
        }

        return $value;
    }
}
