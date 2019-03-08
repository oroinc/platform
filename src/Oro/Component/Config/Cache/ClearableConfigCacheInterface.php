<?php

namespace Oro\Component\Config\Cache;

/**
 * An interface for classes contains a configuration cache and able to clear it.
 */
interface ClearableConfigCacheInterface
{
    /**
     * Clears configuration cache.
     */
    public function clearCache(): void;
}
