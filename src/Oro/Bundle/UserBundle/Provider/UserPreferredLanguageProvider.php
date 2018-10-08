<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\BasePreferredLanguageProvider;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Returns preferred language for User entity based on his or her language chosen in system configuration settings.
 */
class UserPreferredLanguageProvider extends BasePreferredLanguageProvider
{
    /**
     * @var ConfigManager
     */
    private $userConfigManager;

    /**
     * @var LocaleSettings
     */
    private $localeSettings;

    /**
     * @param ConfigManager $userConfigManager
     * @param LocaleSettings $localeSettings
     */
    public function __construct(ConfigManager $userConfigManager, LocaleSettings $localeSettings)
    {
        $this->userConfigManager = $userConfigManager;
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $entity instanceof User;
    }

    /**
     * {@inheritdoc}
     */
    public function getPreferredLanguageForEntity($entity): string
    {
        $userScopeId = $this->userConfigManager->getScopeId();

        $this->userConfigManager->setScopeIdFromEntity($entity);

        $language = $this->localeSettings->getActualLanguage();

        $this->userConfigManager->setScopeId($userScopeId);

        return $language;
    }
}
