<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Oro\Bundle\ApiBundle\Filter\FilterValue;

/**
 * This exception is thrown when the key of a self identifiable filter is not valid.
 * @see \Oro\Bundle\ApiBundle\Filter\SelfIdentifiableFilterInterface
 */
class InvalidFilterValueKeyException extends RuntimeException
{
    private FilterValue $filterValue;

    public function __construct(string $message, FilterValue $filterValue)
    {
        parent::__construct($message);
        $this->filterValue = $filterValue;
    }

    public function getFilterValue(): FilterValue
    {
        return $this->filterValue;
    }
}
