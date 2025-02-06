<?php

namespace Oro\Bundle\LocaleBundle\Manager;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Provides localization entities by passed ids.
 * For the methods with argument useCache - disable using cache,
 * if you like to persist, delete, or assign Localization objects.
 * Cache should be enabled, only if you want to read from the Localization
 */
class LocalizationManager implements ClearableConfigCacheInterface
{
    private const ENTITIES_CACHE_NAMESPACE = 'ORO_LOCALE_LOCALIZATION_DATA';
    private const SIMPLE_CACHE_NAMESPACE = 'ORO_LOCALE_LOCALIZATION_DATA_SIMPLE';

    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;
    private CacheItemPoolInterface $cacheProvider;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        CacheItemPoolInterface $cacheProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->cacheProvider = $cacheProvider;
    }

    public function getLocalization(int $id, bool $useCache = true): ?Localization
    {
        $cacheKey = static::getCacheKey($id);
        $localizations = false;
        if ($useCache) {
            $cacheItem = $this->cacheProvider->getItem($cacheKey);
            $localizations = $cacheItem->isHit() ? $cacheItem->get() : false;
        }

        if (isset($localizations[$id])) {
            return $localizations[$id];
        }

        $localization = $this->getRepository()->find($id);
        if ($localization === null) {
            return null;
        }

        if ($useCache) {
            $cacheItem->set([$id => $localization]);
            $this->cacheProvider->save($cacheItem);
        }

        return $localization;
    }

    /**
     * The application must have possibility to get available localization's data without warming Doctrine metadata.
     * It requires for building the applications cache from the scratch, because in any time the application may need to
     * get this data. But after loading Doctrine metadata for some entities, extended functionality for these entities
     * will not work.
     */
    public function getLocalizationData(int $id, bool $useCache = true): array
    {
        if ($useCache) {
            $cacheItem = $this->cacheProvider->getItem(self::SIMPLE_CACHE_NAMESPACE);
            if ($cacheItem->isHit()) {
                $cacheData = $cacheItem->get();
                if (isset($cacheData[$id])) {
                    return $cacheData[$id];
                }
            }
        }

        $localizationData = $this->fetchLocalizationData();

        if ($useCache) {
            $cacheItem->set($localizationData);
            $this->cacheProvider->save($cacheItem);
        }

        return $localizationData[$id] ?? [];
    }

    private function fetchLocalizationData(): array
    {
        $sql = <<<SQL
            SELECT loc.id, loc.formatting_code AS formatting, lang.code AS language, loc.rtl_mode AS rtl 
            FROM oro_localization AS loc 
            INNER JOIN oro_language AS lang ON lang.id = loc.language_id
        SQL;

        $stmt = $this->doctrineHelper
            ->getEntityManager(Localization::class)
            ->getConnection()
            ->executeQuery($sql);

        $data = [];
        foreach ($stmt->fetchAllAssociative() as $row) {
            $data[$row['id']] = [
                'languageCode' => $row['language'],
                'formattingCode' => $row['formatting'],
                'rtlMode' => (bool) $row['rtl'],
            ];
        }

        return $data;
    }

    public function getLocalizations(array $ids = null, bool $useCache = true): array
    {
        $cacheKey = static::getCacheKey();
        $localizations = false;
        if ($useCache) {
            $cacheItem = $this->cacheProvider->getItem($cacheKey);
            $localizations = $cacheItem->isHit() ? $cacheItem->get() : false;
        }
        if ($localizations === false) {
            $localizations = $this->getRepository()->findAllIndexedById();

            if ($useCache) {
                $cacheItem->set($localizations);
                $this->cacheProvider->saveDeferred($cacheItem);

                foreach ($localizations as $id => $localization) {
                    $cachePoolItem = $this->cacheProvider->getItem(static::getCacheKey($id));
                    $cachePoolItem->set([$id => $localization]);
                    $this->cacheProvider->saveDeferred($cachePoolItem);
                }
                $this->cacheProvider->commit();
            }
        }

        if (null === $ids) {
            return $localizations;
        }

        return array_intersect_key($localizations, array_flip($ids));
    }

    public function getDefaultLocalization(bool $useCache = true): ?Localization
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

    public function clearCache(): void
    {
        $this->cacheProvider->clear();
    }

    protected static function getCacheKey(?int $localizationId = null): string
    {
        return $localizationId !== null
            ? sprintf('%s_%s', self::ENTITIES_CACHE_NAMESPACE, $localizationId)
            : self::ENTITIES_CACHE_NAMESPACE;
    }

    protected function getRepository(): EntityRepository
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(Localization::class);
    }
}
