<?php

namespace Oro\Bundle\LocaleBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Helper\LocalizationCacheHelper;

class LocalizationManager
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var LocalizationRepository
     */
    private $repository;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var LocalizationCacheHelper
     */
    private $localizationCacheHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     * @param LocalizationCacheHelper $localizationCacheHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        LocalizationCacheHelper $localizationCacheHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->localizationCacheHelper = $localizationCacheHelper;
    }

    /**
     * @param int $id
     *
     * @return null|Localization
     */
    public function getLocalization($id)
    {
        $localizations = $this->getLocalizations();

        return isset($localizations[$id]) ? $localizations[$id] : null;
    }

    /**
     * @param array|null $ids
     *
     * @return array|Localization[]
     */
    public function getLocalizations(array $ids = null)
    {
        $cache = $this->localizationCacheHelper->get();

        if ($cache === false) {
            $cache = $this->getRepository()->findBy([], ['name' => 'ASC']);
            $cache = $this->associateLocalizationsArray($cache);
            $this->localizationCacheHelper->save($cache);
        }

        if (null === $ids) {
            return $cache;
        }

        $keys = $this->filterOnlyExistingKeys($ids, $cache);

        return array_intersect_key($cache, array_flip($keys));
    }

    /**
     * @return Localization
     */
    public function getDefaultLocalization()
    {
        $id = (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION));

        $localizations = $this->getLocalizations();

        if (isset($localizations[$id])) {
            return $localizations[$id];
        }

        if (count($localizations)) {
            return reset($localizations);
        }

        return null;
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
