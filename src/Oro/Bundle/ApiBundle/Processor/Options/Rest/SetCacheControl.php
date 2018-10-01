<?php

namespace Oro\Bundle\ApiBundle\Processor\Options\Rest;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets "Cache-Control: max-age=X, public" and "Vary: Origin" response headers
 * if caching of CORS preflight requests is enabled.
 */
class SetCacheControl implements ProcessorInterface
{
    /** @var int */
    private $preflightMaxAge;

    /**
     * @param int $preflightMaxAge The amount of seconds the user agent is allowed to cache CORS preflight requests
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

        if ($this->preflightMaxAge > 0) {
            $responseHeaders = $context->getResponseHeaders();
            if (!$responseHeaders->has('Cache-Control')) {
                // although OPTIONS requests are not cacheable, add "Cache-Control" header
                // indicates that a caching is enabled to prevent making CORS preflight requests not cacheable
                $responseHeaders->set('Cache-Control', \sprintf('max-age=%d, public', $this->preflightMaxAge));
            }
            // the response depends on the Origin header value and should therefore not be served
            // from cache for any other origin
            if (!$responseHeaders->has('Vary')) {
                $responseHeaders->set('Vary', 'Origin');
            }
        }
    }
}
