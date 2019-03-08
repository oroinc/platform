<?php

namespace Oro\Bundle\EntityBundle\Cache;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up entity aliases cache.
 */
class EntityAliasCacheWarmer implements CacheWarmerInterface
{
    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     */
    public function __construct(EntityAliasResolver $entityAliasResolver)
    {
        $this->entityAliasResolver = $entityAliasResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->entityAliasResolver->warmUpCache();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
