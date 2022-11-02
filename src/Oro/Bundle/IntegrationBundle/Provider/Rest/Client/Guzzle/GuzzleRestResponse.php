<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Utils;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Extended response class for the Guzzle REST Client
 */
class GuzzleRestResponse implements RestResponseInterface
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var string
     */
    protected $requestUrl;

    public function __construct(ResponseInterface $response, string $requestUrl = null)
    {
        $this->response = $response;
        $this->requestUrl = $requestUrl;
    }

    /**
     * @return null|string
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyAsString()
    {
        try {
            $result = (string)$this->response->getBody();
        } catch (\Exception $exception) {
            throw GuzzleRestException::createFromException($exception);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($header)
    {
        return $this->response->getHeader($header);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($header)
    {
        return $this->response->hasHeader($header);
    }

    /**
     * {@inheritdoc}
     */
    public function getReasonPhrase()
    {
        return $this->response->getReasonPhrase();
    }

    /**
     * {@inheritdoc}
     */
    public function isClientError()
    {
        return $this->getStatusCode() >= 400 && $this->getStatusCode() < 500;
    }

    /**
     * {@inheritdoc}
     */
    public function isError()
    {
        return $this->isClientError() || $this->isServerError();
    }

    /**
     * {@inheritdoc}
     */
    public function isInformational()
    {
        return $this->getStatusCode() < 200;
    }

    /**
     * {@inheritdoc}
     */
    public function isRedirect()
    {
        return $this->getStatusCode() >= 300 && $this->getStatusCode() < 400;
    }

    /**
     * {@inheritdoc}
     */
    public function isServerError()
    {
        return $this->getStatusCode() >= 500 && $this->getStatusCode() < 600;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return ($this->getStatusCode() >= 200 && $this->getStatusCode() < 300) || $this->getStatusCode() == 304;
    }

    /**
     * {@inheritdoc}
     */
    public function json()
    {
        return Utils::jsonDecode($this->getBodyAsString(), true);
    }

    /**
     * @return ResponseInterface
     */
    public function getSourceResponse()
    {
        return $this->response;
    }
}
