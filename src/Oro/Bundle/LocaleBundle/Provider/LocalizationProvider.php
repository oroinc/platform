<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;

class LocalizationProvider
{
    const CACHE_NAMESPACE = 'ORO_LOCALE_LOCALIZATION_DATA';

    /** @var ObjectRepository */
    protected $repository;

    /** @var ConfigManager */
    protected $configManager;

    /** @var CacheProvider */
    protected $cache;

    /** @var CurrentLocalizationExtensionInterface[] */
    protected $extensions = [];

    /** @var Localization */
    protected $currentLocalization = false;

    /**
     * @param ObjectRepository $repository
     * @param ConfigManager $configManager
     */
    public function __construct(ObjectRepository $repository, ConfigManager $configManager)
    {
        $this->repository = $repository;
        $this->configManager = $configManager;

        /** used to minimize SQL Queries */
        $this->cache = new ArrayCache();
    }

    /**
     * @param string $name
     * @param CurrentLocalizationExtensionInterface $extension
     */
    public function addExtension($name, CurrentLocalizationExtensionInterface $extension)
    {
        $this->extensions[$name] = $extension;
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
            $cache = $this->repository->findBy([], ['name' => 'ASC']);
            $cache = array_combine(
                array_map(function (Localization $element) {
                    return $element->getId();
                }, $cache),
                array_values($cache)
            );
            if ($this->cache) {
                $this->cache->save(self::CACHE_NAMESPACE, $cache);
            }
        }

        return is_null($ids) ? $cache : array_filter(
            $cache,
            function ($value, $key) use ($ids) {
                return in_array($key, $ids, true);
            },
            true
        );
    }

    /**
     * @return Localization
     */
    public function getDefaultLocalization()
    {
        $id = $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION));

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
     * @return Localization|null
     */
    public function getCurrentLocalization()
    {
        if (false === $this->currentLocalization) {
            $this->currentLocalization = null;

            if (!$this->extensions) {
                return null;
            }

            foreach ($this->extensions as $extension) {
                /* @var $extension CurrentLocalizationExtensionInterface */
                if (null !== ($localization = $extension->getCurrentLocalization())) {
                    $this->currentLocalization = $localization;
                    break;
                }
            }
        }

        return $this->currentLocalization;
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
}
