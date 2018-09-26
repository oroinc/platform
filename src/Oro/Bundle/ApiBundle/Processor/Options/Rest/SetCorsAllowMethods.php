<?php

namespace Oro\Bundle\ApiBundle\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\CorsHeaders;
use Oro\Bundle\ApiBundle\Processor\Shared\SetHttpAllowHeader;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "Access-Control-Allow-Methods" response header based on a value of "Allow" response header.
 * After that removes "Allow" response header.
 */
class SetCorsAllowMethods implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var OptionsContext $context */

        $responseHeaders = $context->getResponseHeaders();
        if ($responseHeaders->has(CorsHeaders::ACCESS_CONTROL_ALLOW_METHODS)) {
            return;
        }

        $requestMethod = $context->getRequestHeaders()->get(CorsHeaders::ACCESS_CONTROL_REQUEST_METHOD);
        if ($requestMethod && $responseHeaders->has(SetHttpAllowHeader::RESPONSE_HEADER_NAME)) {
            $responseHeaders->set(
                CorsHeaders::ACCESS_CONTROL_ALLOW_METHODS,
                $responseHeaders->get(SetHttpAllowHeader::RESPONSE_HEADER_NAME)
            );
            $responseHeaders->remove(SetHttpAllowHeader::RESPONSE_HEADER_NAME);
        }
    }
}
