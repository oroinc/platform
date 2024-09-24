<?php

namespace Oro\Bundle\IntegrationBundle\Test;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

/**
 * The response object used by the fake REST client
 */
class FakeRestResponse implements RestResponseInterface
{
    /** @var int */
    protected $code;

    /** @var array */
    protected $headers;

    /** @var string */
    protected $body;

    /**
     * @param int $statusCode HTTP status code
     * @param array $headers list of response headers
     * @param string $body raw response body
     */
    public function __construct($statusCode, array $headers = [], $body = '')
    {
        $this->code = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    #[\Override]
    public function getRequestUrl()
    {
        return '';
    }

    #[\Override]
    public function getBodyAsString()
    {
        return $this->body;
    }

    #[\Override]
    public function getStatusCode()
    {
        return $this->code;
    }

    #[\Override]
    public function getHeader($header)
    {
        return $this->headers[$header];
    }

    #[\Override]
    public function getHeaders()
    {
        return $this->headers;
    }

    #[\Override]
    public function hasHeader($header)
    {
        return array_key_exists($header, $this->headers);
    }

    #[\Override]
    public function isClientError()
    {
        return $this->code > 399 && $this->code < 500;
    }

    #[\Override]
    public function isError()
    {
        return $this->code > 399 && $this->code < 600;
    }

    #[\Override]
    public function isInformational()
    {
        return $this->code < 200;
    }

    #[\Override]
    public function isRedirect()
    {
        return $this->code > 299 && $this->code < 400;
    }

    #[\Override]
    public function isServerError()
    {
        return $this->code > 499 && $this->code < 600;
    }

    #[\Override]
    public function isSuccessful()
    {
        return $this->code < 400;
    }

    #[\Override]
    public function json()
    {
        $data = json_decode((string) $this->body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $exception = new \RuntimeException('Unable to parse response body into JSON: ' . json_last_error());
            throw new RestException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        return $data === null ? [] : $data;
    }

    #[\Override]
    public function getReasonPhrase()
    {
        return 'OK';
    }
}
