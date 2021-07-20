<?php

namespace Oro\Bundle\LocaleBundle\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;

/**
 * Provides localization entities by passed ids.
 */
class LocalizationManager implements WarmableConfigCacheInterface, ClearableConfigCacheInterface
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
        $cacheKey = static::getCacheKey($id);
        $localizations = $useCache ? $this->cacheProvider->fetch($cacheKey) : false;

        if (isset($localizations[$id])) {
            $this->makeLocalizationsManaged($localizations);

            return $localizations[$id];
        }

        /** @var Localization $localization */
        $localization = $this->getRepository()->find($id);
        if ($localization === null) {
            return null;
        }

        if ($useCache) {
            $this->cacheProvider->save($cacheKey, [$id => $localization]);
        }

        return $localization;
    }

    /**
     * The application must have possibility to get available localizations data without warming Doctrine metadata.
     * It requires for building the applications cache from the scratch, because in any time the application may need to
     * get this data. But after loading Doctrine metadata for some entities, extended functionality for this entities
     * will not work.
     *
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
            $sql = 'SELECT loc.id, loc.formatting_code AS formatting, lang.code AS language, loc.rtl_mode AS rtl ' .
                'FROM oro_localization AS loc ' .
                'INNER JOIN oro_language AS lang ON lang.id = loc.language_id';
            $stmt = $this->doctrineHelper->getEntityManager(Localization::class)
                ->getConnection()
                ->executeQuery($sql);
            $cache = [];
            foreach ($stmt->fetchAll() as $row) {
                $cache[$row['id']] = [
                    'languageCode' => $row['language'],
                    'formattingCode' => $row['formatting'],
                    'rtlMode' => (bool)$row['rtl'], # cast to boolean as Mysql stores value as TINYINT(1)
                ];
            }
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
        $cacheKey = static::getCacheKey();
        $localizations = $useCache ? $this->cacheProvider->fetch($cacheKey) : false;

        if ($localizations === false) {
            $localizations = $this->getRepository()->findAllIndexedById();

            if ($useCache) {
                $this->cacheProvider->save($cacheKey, $localizations);

                foreach ($localizations as $id => $localization) {
                    $this->cacheProvider->save(static::getCacheKey($id), [$id => $localization]);
                }
            }
        } else {
            $this->makeLocalizationsManaged($localizations);
        }

        if (null === $ids) {
            return $localizations;
        }

        return array_intersect_key($localizations, array_flip($ids));
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
     * {@inheritdoc}
     */
    public function clearCache(): void
    {
        $this->cacheProvider->deleteAll();
    }

    /**
     * {@inheritdoc}
     */
    public function warmUpCache(): void
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

    private function makeLocalizationsManaged(array $localizations): void
    {
        $unitOfWork = $this->doctrineHelper->getEntityManager(Localization::class)->getUnitOfWork();
        foreach ($localizations as $localization) {
            $unitOfWork->merge($localization);
            $unitOfWork->markReadOnly($localization);
        }
    }
}
