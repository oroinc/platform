<?php

namespace Oro\Component\Layout\Exception;

/**
 * Exception thrown if an argument type does not match with the expected value.
 */
class UnexpectedTypeException extends InvalidArgumentException
{
    /**
     * @param mixed  $value
     * @param string $expectedType
     * @param string $argumentName
     */
    public function __construct($value, $expectedType, $argumentName = null)
    {
        $actualType = is_object($value)
            ? get_class($value)
            : gettype($value);
        $msg        = $argumentName
            ? sprintf(
                'Invalid "%s" argument type. Expected "%s", "%s" given.',
                $argumentName,
                $expectedType,
                $actualType
            )
            : sprintf('Expected argument of type "%s", "%s" given.', $expectedType, $actualType);
        parent::__construct($msg);
    }
}
