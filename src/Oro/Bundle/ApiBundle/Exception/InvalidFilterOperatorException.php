<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown when the requested filtering type is not supported.
 * Or in other words, the filtering by the requested field is enabled,
 * but it does not support the requested operator.
 */
class InvalidFilterOperatorException extends RuntimeException
{
    /** @var string */
    private $operator;

    /**
     * @param string $operator
     */
    public function __construct($operator)
    {
        parent::__construct(sprintf('The operator "%s" is not supported.', $operator));
        $this->operator = $operator;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }
}
