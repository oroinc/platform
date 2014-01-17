<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Common\Cache\Cache;

class TranslationStatisticProvider
{
    const CACHE_KEY = 'translation_statistic';
    const CACHE_TTL = 86400;

    /** @var Cache */
    protected $cache;

    /** @var OroTranslationAdapter */
    protected $adapter;

    /** @var PackagesProvider */
    protected $pm;

    public function __construct(Cache $cache, OroTranslationAdapter $adapter, PackagesProvider $pm)
    {
        $this->cache   = $cache;
        $this->adapter = $adapter;
        $this->pm      = $pm;
    }

    /**
     * Try to get cached statistic data, fetch from backed and save otherwise
     *
     * @return array|mixed
     */
    public function get()
    {
        $data = $this->cache->fetch(static::CACHE_KEY);

        if (false === $data) {
            $data = $this->fetch();

            $this->cache->save(static::CACHE_KEY, $data, static::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Clear cache
     */
    public function clear()
    {
        $this->cache->delete(static::CACHE_KEY);
    }

    /**
     * Fetches data from service
     *
     * @return array
     */
    protected function fetch()
    {
        try {
            $data = $this->adapter->fetchStatistic(
                $this->pm->getInstalledPackages()
            );
        } catch (\Exception $e) {
            $data = [];
        }

        return $data;
    }
}
