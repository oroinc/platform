<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Symfony\Component\Config\ConfigCache as Cache;
use Symfony\Component\Config\ConfigCacheInterface;

/**
 * A factory to create an object is used to store Data API configuration cache.
 */
class ConfigCacheFactory
{
    /** @var string */
    private $cacheDir;

    /** @var bool */
    private $debug;

    /**
     * @param string $cacheDir
     * @param bool   $debug
     */
    public function __construct(string $cacheDir, bool $debug)
    {
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    /**
     * @param string $configKey
     *
     * @return ConfigCacheInterface
     */
    public function getCache(string $configKey): ConfigCacheInterface
    {
        return new Cache(
            sprintf('%s/%s.php', $this->cacheDir, $configKey),
            $this->debug
        );
    }
}
