<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'datetime' type.
 */
class DateTimeTypeCast implements TypeCastingHandlerInterface
{
    #[\Override]
    public function castValue(mixed $value): mixed
    {
        if ($value instanceof \DateTime) {
            return $value;
        }

        if (\is_scalar($value)) {
            throw new TypeCastingException(Query::TYPE_DATETIME);
        }

        return $value;
    }
}
