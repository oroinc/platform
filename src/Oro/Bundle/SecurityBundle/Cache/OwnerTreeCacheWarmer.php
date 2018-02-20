<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class OwnerTreeCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var OwnerTreeProviderInterface
     */
    protected $treeProvider;

    /**
     * @param OwnerTreeProviderInterface $treeProvider
     */
    public function __construct(OwnerTreeProviderInterface $treeProvider)
    {
        $this->treeProvider = $treeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->treeProvider->warmUpCache();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
