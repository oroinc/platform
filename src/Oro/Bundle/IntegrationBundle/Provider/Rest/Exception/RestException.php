<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Exception;

use Oro\Bundle\IntegrationBundle\Exception\TransportException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

class RestException extends TransportException
{
    /**
     * @param RestResponseInterface $response
     * @return RestException
     */
    public static function createFromResponse(RestResponseInterface $response)
    {
        $messageParts[] = '[status code] ' . $response->getStatusCode();
        $messageParts[] = '[reason phrase] ' . $response->getReasonPhrase();
        $message = 'Server error response' . PHP_EOL . implode(PHP_EOL, $messageParts);

        return new static($message, $response->getStatusCode());
    }
}
