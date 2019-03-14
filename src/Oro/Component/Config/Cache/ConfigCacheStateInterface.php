<?php

namespace Oro\Component\Config\Cache;

/**
 * The interface that provides method that can be used to check a state of a configuration cache.
 */
interface ConfigCacheStateInterface
{
    /**
     * Checks if the configuration cache has not been changed since the given timestamp.
     *
     * @param int|null $timestamp The time to compare with the last time the cache was built
     *
     * @return bool TRUE if the the cache has not been changed; otherwise, FALSE
     */
    public function isCacheFresh(?int $timestamp): bool;

    /**
     * Gets timestamp when the configuration cache has been built.
     *
     * @return int|null The last time the cache was built or NULL if the cache is not built yet
     */
    public function getCacheTimestamp(): ?int;
}
