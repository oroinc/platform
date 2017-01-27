<?php

namespace Oro\Bundle\LocaleBundle\Manager;

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
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager  $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
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
        $localizations = $this->getRepository()->findBy([], ['name' => 'ASC']);
        $localizations = array_combine(
            array_map(
                function (Localization $element) {
                    return $element->getId();
                },
                $localizations
            ),
            array_values($localizations)
        );

        if (null === $ids) {
            return $localizations;
        } else {
            $keys = array_filter(
                array_keys($localizations),
                function ($key) use ($ids) {
                    // strict comparing is not allowed because ID might be represented by a string
                    return in_array($key, $ids);
                }
            );

            return array_intersect_key($localizations, array_flip($keys));
        }
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
}
