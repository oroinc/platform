<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Rest\CorsHeaders;
use Oro\Bundle\ApiBundle\Request\Rest\CorsSettings;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "Access-Control-Allow-Origin" response header if CORS request is allowed.
 */
class SetCorsAllowOrigin implements ProcessorInterface
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
        /** @var Context $context */

        $responseHeaders = $context->getResponseHeaders();
        if ($responseHeaders->has(CorsHeaders::ACCESS_CONTROL_ALLOW_ORIGIN)) {
            return;
        }

        $origin = $context->getRequestHeaders()->get(CorsHeaders::ORIGIN);
        if ($origin && $this->isAllowedOrigin($origin)) {
            $responseHeaders->set(CorsHeaders::ACCESS_CONTROL_ALLOW_ORIGIN, $origin);
        }
    }

    private function isAllowedOrigin(string $origin): bool
    {
        $allowedOrigins = $this->corsSettings->getAllowedOrigins();

        foreach ($allowedOrigins as $allowedOrigin) {
            if ('*' === $allowedOrigin || $origin === $allowedOrigin) {
                return true;
            }
        }

        return false;
    }
}
