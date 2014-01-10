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
        $data = false; //@TODO remove debug $this->cache->fetch(static::CACHE_KEY);

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
            $data = $this->adapter->fetchStatistic(
                $this->getInstalledPackages()
            );
        } catch (\Exception $e) {
            $data = [];
        }

        return $data;
    }

    /**
     * Collect installed packages through PackageManger
     * and add bundle namespaces to them
     *
     * @return array
     */
    public function getInstalledPackages()
    {
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

        return array_unique($packages);
    }
}
