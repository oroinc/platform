<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Psr\Log\LoggerInterface;

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

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        Cache $cache,
        OroTranslationAdapter $adapter,
        PackagesProvider $pm,
        LoggerInterface $logger
    ) {
        $this->cache   = $cache;
        $this->adapter = $adapter;
        $this->pm      = $pm;
        $this->logger  = $logger;
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
            if (!empty($data)) {
                $this->cache->save(static::CACHE_KEY, $data, static::CACHE_TTL);
            }
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
            $this->logger->error('Translation statistics fetch failed', ['exception' => $e]);
            $data = [];
        }

        return $data;
    }
}
