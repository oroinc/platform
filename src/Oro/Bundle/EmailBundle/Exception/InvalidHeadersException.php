<?php

namespace Oro\Bundle\EmailBundle\Exception;

use Oro\Bundle\EmailBundle\Mail\Headers;

/**
 * Exception that contains array with invalid headers exceptions array and parsed message headers object.
 */
class InvalidHeadersException extends \Exception
{
    /** @var InvalidHeaderException[] */
    private $exceptions;

    /** @var Headers */
    private $headers;

    /**
     * @param array $exceptions
     * @param Headers $headers
     */
    public function __construct(array $exceptions, Headers $headers)
    {
        $this->exceptions = $exceptions;
        $this->headers = $headers;
    }

    /**
     * @return Headers
     */
    public function getHeaders(): Headers
    {
        return $this->headers;
    }

    /**
     * @return InvalidHeaderException[]
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}
