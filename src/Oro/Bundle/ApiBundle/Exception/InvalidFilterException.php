<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown when the requested filter is not valid by some reasons.
 */
class InvalidFilterException extends RuntimeException
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
