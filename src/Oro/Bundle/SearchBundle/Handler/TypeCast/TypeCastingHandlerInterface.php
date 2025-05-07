<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

/**
 * Interface for type casting handlers.
 */
interface TypeCastingHandlerInterface
{
    /**
     * Cast a value to a specified type.
     */
    public function castValue(mixed $value): mixed;
}
