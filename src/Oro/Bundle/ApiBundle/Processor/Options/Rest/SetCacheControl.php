<?php

namespace Oro\Bundle\ApiBundle\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Request\Rest\CorsSettings;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "Cache-Control: max-age=X, public" and "Vary: Origin" response headers
 * if caching of CORS preflight requests is enabled.
 */
class SetCacheControl implements ProcessorInterface
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

        if ($this->corsSettings->getPreflightMaxAge() > 0) {
            $responseHeaders = $context->getResponseHeaders();
            if (!$responseHeaders->has('Cache-Control')) {
                // although OPTIONS requests are not cacheable, add "Cache-Control" header
                // indicates that a caching is enabled to prevent making CORS preflight requests not cacheable
                $responseHeaders->set(
                    'Cache-Control',
                    sprintf('max-age=%d, public', $this->corsSettings->getPreflightMaxAge())
                );
            }
            // the response depends on the Origin header value and should therefore not be served
            // from cache for any other origin
            if (!$responseHeaders->has('Vary')) {
                $responseHeaders->set('Vary', 'Origin');
            }
        }
    }
}
