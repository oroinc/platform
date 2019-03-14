<?php

namespace Oro\Component\Config\Cache;

/**
 * An interface for classes contains a configuration cache and able to warm up it.
 */
interface WarmableConfigCacheInterface
{
    /**
     * Warms up configuration cache.
     */
    public function warmUpCache(): void;
}
