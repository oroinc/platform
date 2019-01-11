<?php

namespace Oro\Bundle\LocaleBundle\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;

/**
 * Provides localization entities by passed ids.
 */
class LocalizationManager
{
    private const ENTITIES_CACHE_NAMESPACE = 'ORO_LOCALE_LOCALIZATION_DATA';
    private const SIMPLE_CACHE_NAMESPACE = 'ORO_LOCALE_LOCALIZATION_DATA_SIMPLE';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param CacheProvider $cacheProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        CacheProvider $cacheProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param int $id
     * @param bool $useCache disable using cache, if you like to persist, delete, or assign Localization objects.
     *                       Cache should be enabled, only if you want to read from the Localization.
     *
     * @return null|Localization
     */
    public function getLocalization($id, $useCache = true)
    {
        $cache = $useCache ? $this->cacheProvider->fetch(static::getCacheKey($id)) : false;

        if ($cache !== false && array_key_exists($id, $cache)) {
            // make doctrine know about entity from cache
            $this->doctrineHelper
                ->getEntityManager(Localization::class)
                ->getUnitOfWork()
                ->merge($cache[$id]);

            return $cache[$id];
        }

        /** @var Localization $cache */
        $cache = $this->getRepository()->find($id);

        if ($cache === null) {
            return null;
        }

        if ($useCache) {
            $this->cacheProvider->save(static::getCacheKey($id), [$id => $cache]);
        }

        return $cache;
    }

    /**
     * @param int $id
     * @param bool $useCache disable using cache, if you like to persist, delete, or assign Localization objects.
     *                       Cache should be enabled, only if you want to read from the Localization.
     *
     * @return array
     */
    public function getLocalizationData(int $id, $useCache = true): array
    {
        $cache = !$useCache ? false : $this->cacheProvider->fetch(self::SIMPLE_CACHE_NAMESPACE);
        if ($cache === false) {
            $cache = $this->getRepository()->getLocalizationsData();
        }

        if ($useCache) {
            $this->cacheProvider->save(self::SIMPLE_CACHE_NAMESPACE, $cache);
        }

        return $cache[$id] ?? [];
    }

    /**
     * @param array|null $ids
     * @param bool $useCache disable using cache, if you like to persist, delete, or assign Localization objects.
     *                       Cache should be enabled, only if you want to read from the Localization.
     *
     * @return array|Localization[]
     */
    public function getLocalizations(array $ids = null, $useCache = true)
    {
        $cache = $useCache ? $this->cacheProvider->fetch(static::getCacheKey()) : false;

        if ($cache === false) {
            $cache = $this->getRepository()->findBy([], ['name' => 'ASC']);
            $cache = $this->associateLocalizationsArray($cache);

            if ($useCache) {
                $this->cacheProvider->save(static::getCacheKey(), $cache);

                foreach ($cache as $id => $localization) {
                    $this->cacheProvider->save(static::getCacheKey($id), [$id => $localization]);
                }
            }
        }

        if (null === $ids) {
            return $cache;
        }

        $keys = $this->filterOnlyExistingKeys($ids, $cache);

        return array_intersect_key($cache, array_flip($keys));
    }

    /**
     * @param bool $useCache disable using cache, if you like to persist, delete, or assign Localization objects.
     *                       Cache should be enabled, only if you want to read from the Localization.
     *
     * @return Localization
     */
    public function getDefaultLocalization($useCache = true)
    {
        $id = (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION));

        $localizations = $this->getLocalizations(null, $useCache);

        if (isset($localizations[$id])) {
            return $localizations[$id];
        }

        if (count($localizations)) {
            return reset($localizations);
        }

        return null;
    }

    /**
     * @return bool
     */
    public function clearCache()
    {
        return $this->cacheProvider->deleteAll();
    }

    public function warmUpCache()
    {
        $this->clearCache();
        $this->getLocalizations();
        $this->getLocalizationData(0);
    }

    /**
     * @param int $localizationId
     * @return string
     */
    protected static function getCacheKey($localizationId = null)
    {
        return $localizationId !== null
            ? sprintf('%s_%s', self::ENTITIES_CACHE_NAMESPACE, $localizationId)
            : self::ENTITIES_CACHE_NAMESPACE;
    }

    /**
     * @return LocalizationRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(Localization::class);
    }

    /**
     * Set ids of the localizations as keys
     *
     * @param Localization[] $localizations
     * @return Localization[]
     */
    private function associateLocalizationsArray(array $localizations)
    {
        $localizations = array_combine(
            array_map(
                function (Localization $element) {
                    return $element->getId();
                },
                $localizations
            ),
            $localizations
        );

        return $localizations;
    }

    /**
     * @param array $ids
     * @param Localization[] $localizations
     * @return array
     */
    private function filterOnlyExistingKeys(array $ids, $localizations)
    {
        $keys = array_filter(
            array_keys($localizations),
            function ($key) use ($ids) {
                // strict comparing is not allowed because ID might be represented by a string
                return in_array($key, $ids);
            }
        );

        return $keys;
    }
}
