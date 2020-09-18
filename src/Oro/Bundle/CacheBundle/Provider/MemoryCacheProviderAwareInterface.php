<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Interface for memory cache holders.
 */
interface MemoryCacheProviderAwareInterface
{
    /**
     * @param MemoryCacheProviderInterface|null $memoryCacheProvider
     */
    public function setMemoryCacheProvider(?MemoryCacheProviderInterface $memoryCacheProvider): void;
}
