<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\MediaTypeHeaderUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Validates that the current REST API request can be accepted.
 */
class ValidateRequestType implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $requestHeaders = $context->getRequestHeaders();
        if (!$this->isSupportedAcceptHeader($requestHeaders)) {
            throw new NotAcceptableHttpException(
                'Only JSON representation of the requested resource is supported.'
            );
        }
        if (!$this->isSupportedContentTypeHeader($requestHeaders)) {
            throw new NotAcceptableHttpException(
                'The "Content-Type" request header must be "application/json" if specified.'
            );
        }
    }

    private function isSupportedAcceptHeader(ParameterBagInterface $requestHeaders): bool
    {
        $value = $requestHeaders->get('Accept');
        if (!$value) {
            return true;
        }

        if (\is_string($value)) {
            $value = (array)$value;
        }

        foreach ($value as $val) {
            [$mediaType] = MediaTypeHeaderUtil::parseMediaType($val);
            if ('application/json' === $mediaType || 'application/*' === $mediaType || '*/*' === $mediaType) {
                return true;
            }
        }

        return false;
    }

    private function isSupportedContentTypeHeader(ParameterBagInterface $requestHeaders): ?string
    {
        $value = $requestHeaders->get('Content-Type');
        if (!$value) {
            return true;
        }

        [$mediaType] = MediaTypeHeaderUtil::parseMediaType($value);

        return 'application/json' === $mediaType;
    }
}
