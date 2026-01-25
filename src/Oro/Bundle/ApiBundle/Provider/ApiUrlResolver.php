<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Resolves URL reference type based on API request context.
 * Checks if absolute URLs should be used for assets in API responses.
 */
class ApiUrlResolver
{
    public const ABSOLUTE_URL_FLAG = '_api_use_absolute_urls';

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * Checks if absolute URLs should be used (flag set by API processor).
     *
     * @return bool True if absolute URLs should be used
     */
    public function shouldUseAbsoluteUrls(): bool
    {
        return (bool)$this->requestStack->getCurrentRequest()?->attributes->get(self::ABSOLUTE_URL_FLAG, false);
    }

    /**
     * Determines the effective reference type based on API request context.
     *
     * @param int $defaultReferenceType Default reference type to use
     *
     * @return int Effective reference type (ABSOLUTE_URL for API requests if flag is set, otherwise default)
     */
    public function getEffectiveReferenceType(int $defaultReferenceType = UrlGeneratorInterface::ABSOLUTE_PATH): int
    {
        if ($this->shouldUseAbsoluteUrls()) {
            return UrlGeneratorInterface::ABSOLUTE_URL;
        }

        return $defaultReferenceType;
    }
}
