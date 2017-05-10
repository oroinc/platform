<?php

namespace Oro\Bundle\IntegrationBundle\Test;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

class FakeRestResponse implements RestResponseInterface
{
    /** @var int */
    protected $code;

    /** @var array */
    protected $headers;

    /** @var string  */
    protected $body;

    /**
     * FakeRestResponse constructor.
     *
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

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->getMessage();
    }

    /**
     * @inheritDoc
     */
    public function getRequestUrl()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getBodyAsString()
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function getHeader($header)
    {
        return $this->headers[$header];
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($header)
    {
        return array_key_exists($header, $this->headers);
    }

    /**
     * @inheritDoc
     */
    public function isClientError()
    {
        return $this->code > 399 && $this->code < 500;
    }

    /**
     * @inheritDoc
     */
    public function isError()
    {
        return $this->code > 399 && $this->code < 600;
    }

    /**
     * @inheritDoc
     */
    public function isInformational()
    {
        return $this->code < 200;
    }

    /**
     * @inheritDoc
     */
    public function isRedirect()
    {
        return $this->code > 299 && $this->code < 400;
    }

    /**
     * @inheritDoc
     */
    public function isServerError()
    {
        return $this->code > 499 && $this->code < 600;
    }

    /**
     * @inheritDoc
     */
    public function isSuccessful()
    {
        return $this->code < 400;
    }

    /**
     * @inheritDoc
     */
    public function json()
    {
        $data = json_decode((string) $this->body, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw RestException::createFromResponse(
                $this,
                'Unable to parse response body into JSON'
            );
        }

        return $data === null ? array() : $data;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase()
    {
        return 'OK';
    }
}
