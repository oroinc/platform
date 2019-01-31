<?php

namespace Oro\Component\Config\Cache;

use Symfony\Component\Config\ConfigCacheFactoryInterface;

/**
 * The implementation of ConfigCacheFactoryInterface that
 * creates an instance of Oro\Component\Config\Cache\ConfigCache.
 */
class ConfigCacheFactory implements ConfigCacheFactoryInterface
{
    /** @var bool */
    private $debug;

    /**
     * @param bool $debug Whether debugging is enabled or not
     */
    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function cache($file, $callback)
    {
        if (!\is_callable($callback)) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid type for callback argument. Expected callable, but got "%s".',
                \gettype($callback)
            ));
        }

        $cache = new ConfigCache($file, $this->debug);
        if (!$cache->isFresh()) {
            $callback($cache);
        }

        return $cache;
    }
}
