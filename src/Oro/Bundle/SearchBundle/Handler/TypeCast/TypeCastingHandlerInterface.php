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

    /**
     * Will return boolean true if the typecasting handler can cast the value, and false otherwise.
     */
    public function isSupported($value): bool;

    /**
     * A key that specifies the type that the handler can process.
     */
    public static function getType(): string;
}
