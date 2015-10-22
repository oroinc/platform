<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

use Oro\Bundle\EntityConfigBundle\Config\ConfigCacheWarmer;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class EntityCacheWarmer extends CacheWarmer
{
    /** @var ExtendConfigDumper */
    private $dumper;

    /** @var ConfigManager */
    private $configManager;

    /** @var ConfigCacheWarmer */
    private $configCacheWarmer;

    /**
     * @param ExtendConfigDumper $dumper
     * @param ConfigManager      $configManager
     * @param ConfigCacheWarmer  $configCacheWarmer
     */
    public function __construct(
        ExtendConfigDumper $dumper,
        ConfigManager $configManager,
        ConfigCacheWarmer $configCacheWarmer
    ) {
        $this->dumper            = $dumper;
        $this->configManager     = $configManager;
        $this->configCacheWarmer = $configCacheWarmer;
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
