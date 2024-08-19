<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown when a value of a request header is not valid.
 */
class InvalidHeaderValueException extends RuntimeException implements ValidationExceptionInterface
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
