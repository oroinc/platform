<?php

namespace Oro\Component\Config\Cache;

/**
 * An interface for a configuration cache that can clears itself.
 */
interface ClearableConfigCacheInterface
{
    /**
     * Clears configuration cache.
     */
    public function clearCache(): void;
}
