<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Adds "json_api" request type if the "Content-Type" header
 * contains "application/vnd.api+json".
 */
class CheckRequestType implements ProcessorInterface
{
    /**
     * Content-Type of REST API request conforms JSON API specification
     */
    const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $requestType = $context->getRequestType();
        if (!$requestType->contains(RequestType::JSON_API)
            && $this->isJsonApiRequest($context->getRequestHeaders())
        ) {
            $requestType->add(RequestType::JSON_API);
        }
    }

    /**
     * @param ParameterBagInterface $requestHeaders
     *
     * @return bool
     */
    protected function isJsonApiRequest(ParameterBagInterface $requestHeaders)
    {
        $result = false;

        $contentTypeHeader     = $requestHeaders->get('Content-Type');
        $mediaTypeDelimiterPos = strpos($contentTypeHeader, ';');
        if (false === $mediaTypeDelimiterPos) {
            $contentType = $contentTypeHeader;
            $mediaType   = null;
        } else {
            $contentType = substr($contentTypeHeader, 0, $mediaTypeDelimiterPos);
            $mediaType   = substr($contentTypeHeader, $mediaTypeDelimiterPos + 1);
        }

        if ($contentType === self::JSON_API_CONTENT_TYPE) {
            // Servers MUST respond with a 415 Unsupported Media Type status code
            // if a request specifies the header Content-Type: application/vnd.api+json with any media type parameters.
            if (null !== $mediaType) {
                throw new UnsupportedMediaTypeHttpException(
                    'Request\'s "Content-Type" header should not contain any media type parameters.'
                );
            }
            // Servers MUST respond with a 406 Not Acceptable status code if a request's Accept header contains
            // the JSON API media type and all instances of that media type are modified with media type parameters.
            $acceptHeader = array_map('trim', explode(',', $requestHeaders->get('Accept')));
            $isCorrectHeader = true;
            foreach ($acceptHeader as $header) {
                if (strpos($header, self::JSON_API_CONTENT_TYPE) === 0) {
                    $isCorrectHeader = false;
                    if ($header === self::JSON_API_CONTENT_TYPE) {
                        $isCorrectHeader = true;
                        break;
                    }
                }
            }
            if (!$isCorrectHeader) {
                throw new NotAcceptableHttpException(
                    'Not supported "Accept" header. It contains the JSON API content type ' .
                    'and all instances of that are modified with media type parameters.'
                );
            }

            $result = true;
        }

        return $result;
    }
}
