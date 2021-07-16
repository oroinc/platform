<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\AbstractPreferredLocalizationProvider;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Returns preferred localization for User entity based on his or her language chosen in system configuration settings.
 */
class UserPreferredLocalizationProvider extends AbstractPreferredLocalizationProvider
{
    /**
     * @var ConfigManager
     */
    private $userConfigManager;

    /**
     * @var LocaleSettings
     */
    private $localizationManager;

    public function __construct(ConfigManager $userConfigManager, LocalizationManager $localizationManager)
    {
        $this->userConfigManager = $userConfigManager;
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof User;
    }

    /**
     * @param User $entity
     * @return Localization|null
     */
    public function getPreferredLocalizationForEntity($entity): ?Localization
    {
        $originalScopeId = $this->userConfigManager->getScopeId();
        $this->userConfigManager->setScopeIdFromEntity($entity);

        $localization = $this->localizationManager->getLocalization(
            $this->userConfigManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
        );

        $this->userConfigManager->setScopeId($originalScopeId);

        return $localization;
    }
}
