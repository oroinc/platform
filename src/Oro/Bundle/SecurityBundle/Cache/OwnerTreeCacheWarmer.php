<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms cache for tree of owners
 */
class OwnerTreeCacheWarmer implements CacheWarmerInterface
{
    private OwnerTreeProviderInterface $treeProvider;

    public function __construct(OwnerTreeProviderInterface $treeProvider)
    {
        $this->treeProvider = $treeProvider;
    }

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        $this->treeProvider->warmUpCache();
        return [];
    }

    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }
}
