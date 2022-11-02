<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;

/**
 * Implements the base logic of type casting.
 * Allows to validate the value type and then convert it to any type.
 */
abstract class AbstractTypeCastingHandler implements TypeCastingHandlerInterface
{
    /**
     * @param mixed $value
     *
     * @return object|null
     */
    public function castValue($value)
    {
        if (is_scalar($value) || $value instanceof \DateTime) {
            throw new TypeCastingException(static::getType());
        }

        return $value;
    }
}
