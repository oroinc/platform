<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

/**
 * Warms cache of entities.
 */
class EntityCacheWarmer extends CacheWarmer
{
    public function __construct(
        private ExtendConfigDumper $dumper,
        private ConfigManager $configManager,
        private ConfigCacheWarmer $configCacheWarmer,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->configManager->flushAllCaches();
        $this->configCacheWarmer->warmUpCache(ConfigCacheWarmer::MODE_CONFIGURABLE_ENTITY_ONLY);
        $this->dumper->dump();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}
