<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown when the requested sorter is not valid by some reasons.
 */
class InvalidSorterException extends RuntimeException
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
