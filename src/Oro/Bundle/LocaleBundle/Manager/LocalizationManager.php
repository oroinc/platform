<?php

namespace Oro\Bundle\LocaleBundle\Manager;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;

class LocalizationManager
{
    const CACHE_NAMESPACE = 'ORO_LOCALE_LOCALIZATION_DATA';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var LocalizationRepository
     */
    protected $repository;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;

        /** used to minimize SQL Queries */
        $this->cache = new ArrayCache();
    }

    /**
     * @param int $id
     *
     * @return null|Localization
     */
    public function getLocalization($id)
    {
        $cache = $this->getLocalizations();

        return isset($cache[$id]) ? $cache[$id] : null;
    }

    /**
     * @param array|null $ids
     *
     * @return array|Localization[]
     */
    public function getLocalizations(array $ids = null)
    {
        $cache = $this->cache ? $this->cache->fetch(self::CACHE_NAMESPACE) : false;

        if ($cache === false) {
            $cache = $this->getRepository()->findBy([], ['name' => 'ASC']);
            $cache = array_combine(
                array_map(
                    function (Localization $element) {
                        return $element->getId();
                    },
                    $cache
                ),
                array_values($cache)
            );
            if ($this->cache) {
                $this->cache->save(self::CACHE_NAMESPACE, $cache);
            }
        }

        if (null === $ids) {
            return $cache;
        } else {
            $keys = array_filter(
                array_keys($cache),
                function ($key) use ($ids) {
                    // strict comparing is not allowed because ID might be represented by a string
                    return in_array($key, $ids);
                }
            );

            return array_intersect_key($cache, array_flip($keys));
        }
    }

    /**
     * @return Localization
     */
    public function getDefaultLocalization()
    {
        $id = (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION));

        $localization = $this->getLocalization($id);

        if ($localization instanceof Localization) {
            return $localization;
        }

        $localizations = $this->getLocalizations();
        if (count($localizations)) {
            return reset($localizations);
        }

        return null;
    }

    /**
     * Warms up the cache
     */
    public function warmUpCache()
    {
        if ($this->cache) {
            $this->clearCache();
            $this->getLocalizations();
        }
    }

    /**
     * Clears the cache
     */
    public function clearCache()
    {
        if ($this->cache) {
            $this->cache->delete(self::CACHE_NAMESPACE);
        }
    }

    /**
     * @return LocalizationRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->doctrineHelper->getEntityRepositoryForClass(Localization::class);
        }

        return $this->repository;
    }
}
