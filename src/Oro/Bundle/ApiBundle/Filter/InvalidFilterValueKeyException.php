<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;

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
