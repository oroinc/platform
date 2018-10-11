<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Create\Rest\SetLocationHeader;
use Oro\Bundle\ApiBundle\Processor\DeleteList\SetDeletedCountHeader;
use Oro\Bundle\ApiBundle\Processor\Shared\SetTotalCountHeader;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "Access-Control-Allow-Headers" response header
 * to allow including the following headers on CORS preflight requests:
 * * Content-Type
 * * X-Include
 * Sets "Access-Control-Expose-Headers" response header
 * to allows exposing the following headers from CORS responses:
 * * Location
 * * X-Include-Total-Count
 * * X-Include-Deleted-Count
 * Sets "Access-Control-Allow-Credentials" response header
 * if cookies (or other user credentials) is allowed to be included on CORS requests.
 */
class SetCorsAllowAndExposeHeaders implements ProcessorInterface
{
    /** @var string[] */
    private $allowedHeaders;

    /** @var string[] */
    private $exposableHeaders;

    /** @var bool */
    private $isCredentialsAllowed;

    /**
     * @param string[] $allowedHeaders
     * @param string[] $exposableHeaders
     * @param bool     $isCredentialsAllowed
     */
    public function __construct(array $allowedHeaders, array $exposableHeaders, bool $isCredentialsAllowed)
    {
        $this->allowedHeaders = $allowedHeaders;
        $this->exposableHeaders = $exposableHeaders;
        $this->isCredentialsAllowed = $isCredentialsAllowed;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $responseHeaders = $context->getResponseHeaders();
        if (!$responseHeaders->has(CorsHeaders::ACCESS_CONTROL_ALLOW_HEADERS)
            && $context->getRequestHeaders()->get(CorsHeaders::ACCESS_CONTROL_REQUEST_METHOD)
        ) {
            $responseHeaders->set(
                CorsHeaders::ACCESS_CONTROL_ALLOW_HEADERS,
                self::getHeaders(
                    [
                        'Content-Type',
                        Context::INCLUDE_HEADER
                    ],
                    $this->allowedHeaders
                )
            );
        }
        if (!$responseHeaders->has(CorsHeaders::ACCESS_CONTROL_EXPOSE_HEADERS)) {
            $responseHeaders->set(
                CorsHeaders::ACCESS_CONTROL_EXPOSE_HEADERS,
                self::getHeaders(
                    [
                        SetLocationHeader::RESPONSE_HEADER_NAME,
                        SetTotalCountHeader::RESPONSE_HEADER_NAME,
                        SetDeletedCountHeader::RESPONSE_HEADER_NAME
                    ],
                    $this->exposableHeaders
                )
            );
        }
        if ($this->isCredentialsAllowed && !$responseHeaders->has(CorsHeaders::ACCESS_CONTROL_ALLOW_CREDENTIALS)) {
            $responseHeaders->set(CorsHeaders::ACCESS_CONTROL_ALLOW_CREDENTIALS, 'true');
        }
    }

    /**
     * @param string[] $defaultHeaders
     * @param string[] $customHeaders
     *
     * @return string
     */
    private static function getHeaders(array $defaultHeaders, array $customHeaders): string
    {
        return \implode(',', \array_merge($defaultHeaders, $customHeaders));
    }
}
