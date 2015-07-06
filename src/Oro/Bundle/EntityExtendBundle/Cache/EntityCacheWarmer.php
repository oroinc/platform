<?php

namespace Oro\Bundle\EntityExtendBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class EntityCacheWarmer extends CacheWarmer
{
    /** @var ExtendConfigDumper */
    private $dumper;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ExtendConfigDumper $dumper
     * @param ConfigManager      $configManager
     */
    public function __construct(ExtendConfigDumper $dumper, ConfigManager $configManager)
    {
        $this->dumper        = $dumper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->configManager->clearCache();
        $this->configManager->clearConfigurableCache();
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
