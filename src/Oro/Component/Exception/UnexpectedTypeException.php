<?php

namespace Oro\Component\Exception;

/**
 * Thrown when a value does not match an expected type.
 */
class UnexpectedTypeException extends \RuntimeException
{
    /**
     * @param mixed $value
     * @param string $expectedType
     */
    public function __construct($value, $expectedType)
    {
        $message = sprintf(
            'Expected argument of type "%s", "%s" given',
            $expectedType,
            is_object($value) ? get_class($value) : gettype($value)
        );

        parent::__construct($message);
    }
}
