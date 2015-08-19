<?php

namespace Oro\Component\ConfigExpression\Exception;

/**
 * Exception thrown if an argument type does not match with the expected value.
 */
class UnexpectedTypeException extends InvalidArgumentException
{
    /**
     * @param mixed  $value
     * @param string $expectedType
     * @param string $message
     */
    public function __construct($value, $expectedType, $message = null)
    {
        $actualType = is_object($value)
            ? get_class($value)
            : gettype($value);
        $msg        = $message
            ? sprintf('%s Expected "%s", "%s" given.', $message, $expectedType, $actualType)
            : sprintf('Expected argument of type "%s", "%s" given.', $expectedType, $actualType);
        parent::__construct($msg);
    }
}
