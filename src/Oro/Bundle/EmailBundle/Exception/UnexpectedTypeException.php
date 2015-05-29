<?php

namespace Oro\Bundle\EmailBundle\Exception;

/**
 * Exception thrown if an argument type does not match with the expected value.
 */
class UnexpectedTypeException extends \InvalidArgumentException
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
