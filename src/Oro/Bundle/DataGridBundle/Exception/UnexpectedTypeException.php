<?php

namespace Oro\Bundle\DataGridBundle\Exception;

/**
 * Thrown when a value of an unexpected type is encountered in datagrid processing.
 */
class UnexpectedTypeException extends InvalidArgumentException
{
    /**
     * @param mixed $value
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
