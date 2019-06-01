<?php

namespace Oro\Bundle\ApiBundle\Exception;

use Oro\Bundle\ApiBundle\Filter\FilterValue;

/**
 * This exception is thrown when the key of a self identifiable filter is not valid.
 * @see \Oro\Bundle\ApiBundle\Filter\SelfIdentifiableFilterInterface
 */
class InvalidFilterValueKeyException extends RuntimeException
{
    /** @var FilterValue */
    private $filterValue;

    /**
     * @param string      $message
     * @param FilterValue $filterValue
     */
    public function __construct($message, FilterValue $filterValue)
    {
        parent::__construct($message);
        $this->filterValue = $filterValue;
    }

    /**
     * @return FilterValue
     */
    public function getFilterValue()
    {
        return $this->filterValue;
    }
}
