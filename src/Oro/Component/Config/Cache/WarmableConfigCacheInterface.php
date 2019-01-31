<?php

namespace Oro\Component\Config\Cache;

/**
 * An tnterface for classes contains aconfiguration cache and able to warm up it.
 */
interface WarmableConfigCacheInterface
{
    /**
     * Warms up configuration cache.
     */
    public function warmUpCache(): void;
}
