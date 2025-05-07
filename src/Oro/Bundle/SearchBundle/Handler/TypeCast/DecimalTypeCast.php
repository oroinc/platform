<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Brick\Math\BigDecimal;
use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Ensures that the data added to the search index is of the appropriate 'decimal' type.
 */
class DecimalTypeCast implements TypeCastingHandlerInterface
{
    #[\Override]
    public function castValue(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return BigDecimal::of($value)->toFloat();
        }

        if (\is_scalar($value) || $value instanceof \DateTime) {
            throw new TypeCastingException(Query::TYPE_DECIMAL);
        }

        return $value;
    }
}
