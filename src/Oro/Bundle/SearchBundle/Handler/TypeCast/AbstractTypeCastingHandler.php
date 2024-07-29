<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;

/**
 * Implements the base logic of type casting.
 * Allows validating the value type and then convert it to any type.
 */
abstract class AbstractTypeCastingHandler implements TypeCastingHandlerInterface
{
    public function castValue(mixed $value): mixed
    {
        if (is_scalar($value) || $value instanceof \DateTime) {
            throw new TypeCastingException(static::getType());
        }

        return $value;
    }
}
