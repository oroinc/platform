<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use Guzzle\Http\Message\Response as GuzzleResponse;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

class GuzzleRestResponse implements RestResponseInterface
{
    /**
     * @var GuzzleResponse
     */
    protected $response;

    /**
     * @var string
     */
    protected $requestUrl;

    /**
     * @param GuzzleResponse $response
     * @param string $requestUrl
     */
    public function __construct(GuzzleResponse $response, $requestUrl = null)
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
    public function __toString()
    {
        try {
            $result = (string)$this->response;
        } catch (\Exception $exception) {
            throw GuzzleRestException::createFromException($exception);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyAsString()
    {
        try {
            $result = $this->response->getBody(true);
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
    public function getMessage()
    {
        try {
            $result = $this->response->getMessage();
        } catch (\Exception $exception) {
            throw GuzzleRestException::createFromException($exception);
        }
        return $result;
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
        return $this->response->isClientError();
    }

    /**
     * {@inheritdoc}
     */
    public function isError()
    {
        return $this->response->isError();
    }

    /**
     * {@inheritdoc}
     */
    public function isInformational()
    {
        return $this->response->isInformational();
    }

    /**
     * {@inheritdoc}
     */
    public function isRedirect()
    {
        return $this->response->isRedirect();
    }

    /**
     * {@inheritdoc}
     */
    public function isServerError()
    {
        return $this->response->isServerError();
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        return $this->response->isSuccessful();
    }

    /**
     * {@inheritdoc}
     */
    public function json()
    {
        try {
            $result = $this->response->json();
        } catch (\Exception $exception) {
            throw GuzzleRestException::createFromException($exception);
        }
        return $result;
    }

    /**
     * @return GuzzleResponse
     */
    public function getSourceResponse()
    {
        return $this->response;
    }
}
