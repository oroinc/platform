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
     *
     * @param $value
     *
     * @return bool
     */
    public function isSupported($value): bool;

    /**
     * A key that specifies the type that the handler can process.
     *
     * @return string
     */
    public static function getType(): string;
}
