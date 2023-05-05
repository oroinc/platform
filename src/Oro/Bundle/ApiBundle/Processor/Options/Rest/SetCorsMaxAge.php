<?php

namespace Oro\Bundle\ApiBundle\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Request\Rest\CorsHeaders;
use Oro\Bundle\ApiBundle\Request\Rest\CorsSettings;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "Access-Control-Max-Age" response header
 * if the caching of CORS preflight requests is enabled.
 */
class SetCorsMaxAge implements ProcessorInterface
{
    private CorsSettings $corsSettings;

    public function __construct(CorsSettings $corsSettings)
    {
        $this->corsSettings = $corsSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var OptionsContext $context */

        $responseHeaders = $context->getResponseHeaders();
        if ($this->corsSettings->getPreflightMaxAge() > 0
            && !$responseHeaders->has(CorsHeaders::ACCESS_CONTROL_MAX_AGE)
            && $context->getRequestHeaders()->has(CorsHeaders::ACCESS_CONTROL_REQUEST_METHOD)
        ) {
            $responseHeaders->set(CorsHeaders::ACCESS_CONTROL_MAX_AGE, $this->corsSettings->getPreflightMaxAge());
        }
    }
}
