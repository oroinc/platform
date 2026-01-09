<?php

namespace Oro\Bundle\AssetBundle\Cache;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up cache for subresource integrity hash.
 */
class SubresourceIntegrityCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private SubresourceIntegrityProvider $integrityProvider)
    {
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->integrityProvider->warmUpCache();

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }
}
