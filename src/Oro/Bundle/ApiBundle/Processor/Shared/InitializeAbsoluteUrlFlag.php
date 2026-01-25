<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sets a flag in request attributes to indicate that absolute URLs should be used for assets.
 * This processor runs only for API requests when use_absolute_urls_for_api configuration is enabled.
 */
class InitializeAbsoluteUrlFlag implements ProcessorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly bool $useAbsoluteUrlsForApi
    ) {
    }

    public function process(ContextInterface $context): void
    {
        if (!$this->useAbsoluteUrlsForApi) {
            return;
        }

        $this->requestStack->getCurrentRequest()?->attributes->set(ApiUrlResolver::ABSOLUTE_URL_FLAG, true);
    }
}
