<?php

namespace Oro\Bundle\ApiBundle\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\CorsHeaders;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "Access-Control-Max-Age" response header
 * if the caching of CORS preflight requests is enabled.
 */
class SetCorsMaxAge implements ProcessorInterface
{
    /** @var int */
    private $preflightMaxAge;

    /**
     * @param int $preflightMaxAge The amount of seconds the user agent is allowed to cache CORS preflight request
     */
    public function __construct(int $preflightMaxAge)
    {
        $this->preflightMaxAge = $preflightMaxAge;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var OptionsContext $context */

        $responseHeaders = $context->getResponseHeaders();
        if ($this->preflightMaxAge > 0
            && !$responseHeaders->has(CorsHeaders::ACCESS_CONTROL_MAX_AGE)
            && $context->getRequestHeaders()->has(CorsHeaders::ACCESS_CONTROL_REQUEST_METHOD)
        ) {
            $responseHeaders->set(CorsHeaders::ACCESS_CONTROL_MAX_AGE, $this->preflightMaxAge);
        }
    }
}
