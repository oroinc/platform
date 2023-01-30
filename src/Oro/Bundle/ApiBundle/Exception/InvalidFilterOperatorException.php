<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown when the requested filtering type is not supported.
 * Or in other words, the filtering by the requested field is enabled,
 * but it does not support the requested operator.
 */
class InvalidFilterOperatorException extends RuntimeException
{
    private string $operator;

    public function __construct(string $operator)
    {
        parent::__construct(sprintf('The operator "%s" is not supported.', $operator));
        $this->operator = $operator;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }
}
