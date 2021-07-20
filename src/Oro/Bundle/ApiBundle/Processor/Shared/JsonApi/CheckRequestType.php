<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Shared\CheckRequestType as BaseCheckRequestType;
use Oro\Bundle\ApiBundle\Request\MediaTypeHeaderUtil;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Adds the "json_api" aspect to the request type if the current API request is JSON:API request.
 */
class CheckRequestType extends BaseCheckRequestType
{
    private const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    /**
     * {@inheritdoc}
     */
    protected function checkRequestType(Context $context): bool
    {
        $detected = false;
        $requestType = $context->getRequestType();
        if (!$requestType->contains(RequestType::JSON_API)
            && $this->checkJsonApiRequest($context->getRequestHeaders())
        ) {
            $requestType->add(RequestType::JSON_API);
            $detected = true;
        }

        return $detected;
    }

    private function checkJsonApiRequest(ParameterBagInterface $requestHeaders): bool
    {
        $result = false;

        $acceptHeaderValues = $this->getAcceptHeaderValues($requestHeaders);
        if ($acceptHeaderValues && $this->checkAcceptHeader($acceptHeaderValues)) {
            $result = true;
        }

        $contentTypeHeaderValue = $this->getContentTypeHeaderValue($requestHeaders);
        if ($contentTypeHeaderValue && $this->checkContentTypeHeader($contentTypeHeaderValue)) {
            if (!$result && !$this->isJsonApiContentTypeAccepted($acceptHeaderValues)) {
                throw new NotAcceptableHttpException(
                    'The "Accept" request header does not accept JSON:API content type.'
                );
            }
            $result = true;
        }

        return $result;
    }

    /**
     * @param ParameterBagInterface $requestHeaders
     *
     * @return string[]|null
     */
    private function getAcceptHeaderValues(ParameterBagInterface $requestHeaders): ?array
    {
        $value = $requestHeaders->get('Accept');
        if (!$value) {
            return null;
        }

        if (\is_string($value)) {
            $value = (array)$value;
        }

        return $value;
    }

    private function getContentTypeHeaderValue(ParameterBagInterface $requestHeaders): ?string
    {
        $value = $requestHeaders->get('Content-Type');
        if (!$value) {
            return null;
        }

        return $value;
    }

    /**
     * @param string[] $acceptHeaderValues
     *
     * @return bool
     */
    private function checkAcceptHeader(array $acceptHeaderValues): bool
    {
        $result = false;
        $hasJsonApiMediaType = false;
        $hasJsonApiMediaTypeWithoutParameters = false;
        foreach ($acceptHeaderValues as $value) {
            [$mediaType, $mediaTypeParameters] = MediaTypeHeaderUtil::parseMediaType($value);
            if (self::JSON_API_MEDIA_TYPE === $mediaType) {
                $hasJsonApiMediaType = true;
                if (!$mediaTypeParameters) {
                    $hasJsonApiMediaTypeWithoutParameters = true;
                }
            }
        }
        if ($hasJsonApiMediaType) {
            if (!$hasJsonApiMediaTypeWithoutParameters) {
                // in case Accept header contains at least one instance of JSON:API media type,
                // JSON:API v1.0 requires at least one of these instances without any media type parameters
                throw new NotAcceptableHttpException(
                    'The "Accept" request header should contains at least one instance of JSON:API media type'
                    . ' without any parameters.'
                );
            }
            $result = true;
        }

        return $result;
    }

    private function checkContentTypeHeader(string $contentTypeHeaderValue): bool
    {
        $result = false;
        [$mediaType, $mediaTypeParameters] = MediaTypeHeaderUtil::parseMediaType($contentTypeHeaderValue);
        if (self::JSON_API_MEDIA_TYPE === $mediaType) {
            if ($mediaTypeParameters) {
                // JSON:API v1.0 does not allow JSON:API media type with any parameters
                throw new UnsupportedMediaTypeHttpException(
                    'The "Content-Type" request header should contain JSON:API media type without any parameters.'
                );
            }
            $result = true;
        }

        return $result;
    }

    /**
     * @param string[]|null $acceptHeaderValues
     *
     * @return bool
     */
    private function isJsonApiContentTypeAccepted(?array $acceptHeaderValues): bool
    {
        if (!$acceptHeaderValues) {
            return true;
        }
        foreach ($acceptHeaderValues as $value) {
            [$mediaType] = MediaTypeHeaderUtil::parseMediaType($value);
            if (self::JSON_API_MEDIA_TYPE !== $mediaType
                && (
                    '*/*' === $mediaType
                    || 'application/*' === $mediaType
                    || 'application/json' === $mediaType
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
