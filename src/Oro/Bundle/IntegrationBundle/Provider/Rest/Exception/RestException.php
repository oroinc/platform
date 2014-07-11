<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Exception;

use Oro\Bundle\IntegrationBundle\Exception\TransportException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

class RestException extends TransportException
{
    /**
     * @var RestResponseInterface
     */
    protected $response;

    /**
     * @param RestResponseInterface $response
     * @param string|null $message
     * @param \Exception|null $previous
     * @return RestException
     */
    public static function createFromResponse(
        RestResponseInterface $response,
        $message = null,
        \Exception $previous = null
    ) {
        if ($response->isClientError()) {
            $label = 'Client error response';
        } elseif ($response->isServerError()) {
            $label = 'Server error response';
        } else {
            $label = 'Unsuccessful response';
        }

        if ($message) {
            $message = $label . ': ' . $message;
        } else {
            $message = $label;
        }

        $messageParts[] = '[status code] ' . $response->getStatusCode();
        $messageParts[] = '[reason phrase] ' . $response->getReasonPhrase();
        $url = $response->getRequestUrl();
        if ($url) {
            $messageParts[] = '[url] ' . $response->getRequestUrl();
        }
        $body = $response->getBodyAsString();
        if ($body) {
            $messageParts[] = '[response body] ' . $response->getBodyAsString();
        }
        $message = $message . PHP_EOL . implode(PHP_EOL, $messageParts);

        /** @var RestException $result */
        $result = new static($message, $response->getStatusCode(), $previous);
        $result->setResponse($response);

        return $result;
    }

    /**
     * @param RestResponseInterface $response
     */
    public function setResponse(RestResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return RestResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
