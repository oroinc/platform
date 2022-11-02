<?php

namespace Oro\Bundle\SearchBundle\Handler\TypeCast;

/**
 * Interface for type casting handlers.
 */
interface TypeCastingHandlerInterface
{
    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function castValue($value);

    /**
     * Will return boolean true if the typecasting handler can cast the value, and false otherwise.
     */
    public function isSupported($value): bool;

    /**
     * A key that specifies the type that the handler can process.
     */
    public static function getType(): string;
}
