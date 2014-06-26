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
     * @param GuzzleResponse $response
     */
    public function __construct(GuzzleResponse $response)
    {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string)$this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyAsString()
    {
        return $this->response->getBody(true);
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
        return $this->response->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getRawHeaders()
    {
        return $this->response->getRawHeaders();
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
    public function getContentEncoding()
    {
        return $this->response->getContentEncoding();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentLanguage()
    {
        return $this->response->getContentLanguage();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentLength()
    {
        return $this->response->getContentLength();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentLocation()
    {
        return $this->response->getContentLocation();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentDisposition()
    {
        return $this->response->getContentDisposition();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentMd5()
    {
        return $this->response->getContentMd5();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentRange()
    {
        return $this->response->getContentRange();
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        return $this->response->getContentType();
    }

    /**
     * {@inheritdoc}
     */
    public function isContentType($type)
    {
        return $this->response->isContentType($type);
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
        return $this->response->json();
    }

    /**
     * {@inheritdoc}
     */
    public function xml()
    {
        return $this->response->xml();
    }

    /**
     * {@inheritdoc}
     */
    public function getRedirectCount()
    {
        return $this->response->getRedirectCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getEffectiveUrl()
    {
        return $this->response->getEffectiveUrl();
    }

    /**
     * @return GuzzleResponse
     */
    public function getSourceResponse()
    {
        return $this->response;
    }
}
