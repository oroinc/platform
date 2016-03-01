<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

class SetResponseContentType implements ProcessorInterface
{
    /**
     * Content-Type of REST API response conforms JSON API specification
     */
    const JSON_API_CONTENT_TYPE = 'application/vnd.api+json';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $responseHeaders = $context->getResponseHeaders();
        if (!$responseHeaders->has('Content-Type')) {
            $context->getResponseHeaders()->set('Content-Type', self::JSON_API_CONTENT_TYPE);
        }
    }
}
