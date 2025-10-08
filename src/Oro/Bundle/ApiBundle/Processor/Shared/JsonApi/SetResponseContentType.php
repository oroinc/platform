<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "application/vnd.api+json" as the value of the response "Content-Type" header
 * if this header is not set yet.
 */
class SetResponseContentType implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $responseHeaders = $context->getResponseHeaders();
        if (!$responseHeaders->has('Content-Type')) {
            $context->getResponseHeaders()->set('Content-Type', 'application/vnd.api+json');
        }
    }
}
