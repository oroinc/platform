<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "Access-Control-Allow-Origin" response header if CORS request is allowed.
 */
class SetCorsAllowOrigin implements ProcessorInterface
{
    /** @var string[] */
    private $allowedOrigins;

    /**
     * @param string[] $allowedOrigins
     */
    public function __construct(array $allowedOrigins)
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $responseHeaders = $context->getResponseHeaders();
        if ($responseHeaders->has(CorsHeaders::ACCESS_CONTROL_ALLOW_ORIGIN)) {
            return;
        }

        $origin = $context->getRequestHeaders()->get(CorsHeaders::ORIGIN);
        if ($origin && \in_array($origin, $this->allowedOrigins, true)) {
            $responseHeaders->set(CorsHeaders::ACCESS_CONTROL_ALLOW_ORIGIN, $origin);
        }
    }
}
