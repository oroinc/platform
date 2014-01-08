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

    public function __construct(Cache $cache, OroTranslationAdapter $adapter)
    {
        $this->cache   = $cache;
        $this->adapter = $adapter;
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

            // $this->cache->save(static::CACHE_KEY, $data, static::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Fetches data from service
     *
     * @return array
     */
    protected function fetch()
    {
        try {
            // @TODO collect packages
            $data = $this->adapter->fetchStatistic();
        } catch (\Exception $e) {
            $data = [];
        }

        return $data;
    }
}
