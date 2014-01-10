<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Common\Cache\Cache;

use Composer\Package\PackageInterface;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;

class TranslationStatisticProvider
{
    const CACHE_KEY = 'translation_statistic';
    const CACHE_TTL = 86400;

    /** @var Cache */
    protected $cache;

    /** @var OroTranslationAdapter */
    protected $adapter;

    /** @var PackageManager */
    protected $pm;

    /** @var array */
    protected $bundles;

    public function __construct(Cache $cache, OroTranslationAdapter $adapter, PackageManager $pm, array $bundles)
    {
        $this->cache   = $cache;
        $this->adapter = $adapter;
        $this->pm      = $pm;
        $this->bundles = $bundles;
    }

    /**
     * Try to get cached statistic data, fetch from backed and save otherwise
     *
     * @return array|mixed
     */
    public function get()
    {
        $data = false; //$this->cache->fetch(static::CACHE_KEY);

        if (false === $data) {
            $data = $this->fetch();

            $this->cache->save(static::CACHE_KEY, $data, static::CACHE_TTL);
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
            // collect installed packages through PackageManger
            $packages = $this->pm->getInstalled();
            $packages = array_map(
                function (PackageInterface $package) {
                    return $package->getName();
                },
                $packages
            );

            // collect bundle namespaces
            foreach ($this->bundles as $bundle) {
                $namespaceParts = explode('\\', $bundle);
                $packages[]     = reset($namespaceParts);
            }

            $data = $this->adapter->fetchStatistic(array_unique($packages));
        } catch (\Exception $e) {
            $data = [];
        }

        return $data;
    }
}
