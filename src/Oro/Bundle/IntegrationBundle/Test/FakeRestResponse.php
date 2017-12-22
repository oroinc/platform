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
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestUrl()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyAsString()
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($header)
    {
        return $this->headers[$header];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($header)
    {
        return array_key_exists($header, $this->headers);
    }

    /**
     * {@inheritdoc}
     */
    public function isClientError()
    {
        return $this->code > 399 && $this->code < 500;
    }

    /**
     * {@inheritdoc}
     */
    public function isError()
    {
        return $this->code > 399 && $this->code < 600;
    }

    /**
     * {@inheritdoc}
     */
    public function isInformational()
    {
        return $this->code < 200;
    }

    /**
     * {@inheritdoc}
     */
    public function isRedirect()
    {
        return $this->code > 299 && $this->code < 400;
    }

    /**
     * {@inheritdoc}
     */
    public function isServerError()
    {
        return $this->code > 499 && $this->code < 600;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return $this->code < 400;
    }

    /**
     * {@inheritdoc}
     */
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

        return $data === null ? array() : $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        return 'OK';
    }
}
