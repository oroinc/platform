<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Interface for memory cache holders.
 */
interface MemoryCacheProviderAwareInterface
{
    public function setMemoryCacheProvider(?MemoryCacheProviderInterface $memoryCacheProvider): void;
}
