<?php

namespace Oro\Bundle\LocaleBundle\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\TranslationBundle\Api\PredefinedLanguageCodeResolverInterface;

/**
 * Resolves the "user" predefined language code as a default language for the current user.
 */
class UserPredefinedLanguageCodeResolver implements PredefinedLanguageCodeResolverInterface
{
    private LocalizationManager $localizationManager;
    private ConfigManager $configManager;

    public function __construct(LocalizationManager $localizationManager, ConfigManager $configManager)
    {
        $this->localizationManager = $localizationManager;
        $this->configManager = $configManager;
    }

    #[\Override]
    public function getDescription(): string
    {
        return <<<MARKDOWN
**user** for a default language for the current user.
MARKDOWN;
    }

    #[\Override]
    public function resolve(): string
    {
        $localization = $this->localizationManager->getLocalizationData(
            (int)$this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION)
            )
        );

        return $localization['languageCode'] ?? Configuration::DEFAULT_LANGUAGE;
    }
}
