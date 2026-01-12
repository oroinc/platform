<?php

namespace Oro\Bundle\ConfigBundle\Exception;

/**
 * Thrown when a configuration value or object has an unexpected type.
 *
 * This exception is raised when a configuration component receives a value or object
 * that does not match the expected type. It provides a clear error message indicating
 * both the expected type and the actual type that was provided, facilitating debugging
 * of configuration-related type mismatches.
 */
class UnexpectedTypeException extends \RuntimeException
{
    /**
     * @param mixed  $value
     * @param string $expectedType
     */
    public function __construct($value, $expectedType)
    {
        parent::__construct(
            sprintf(
                'Expected argument of type "%s", "%s" given',
                $expectedType,
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }
}
