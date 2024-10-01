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
    public function warmUp($cacheDir)
    {
        $this->treeProvider->warmUpCache();
    }

    #[\Override]
    public function isOptional()
    {
        return true;
    }
}
