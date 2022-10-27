<?php

namespace Oro\Component\Config\Cache;

/**
 * An interface for a configuration cache that can warms up itself.
 */
interface WarmableConfigCacheInterface
{
    /**
     * Warms up configuration cache.
     */
    public function warmUpCache(): void;
}
