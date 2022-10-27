<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown when the requested sorter is not valid by some reasons.
 */
class InvalidSorterException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
