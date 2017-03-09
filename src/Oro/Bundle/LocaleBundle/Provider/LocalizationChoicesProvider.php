<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

class LocalizationChoicesProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var LocalizationManager */
    protected $localizationManager;

    /**
     * @param ConfigManager $configManager
     * @param LocalizationManager $localizationManager
     * @param LanguageProvider $languageProvider
     */
    public function __construct(
        ConfigManager $configManager,
        LocalizationManager $localizationManager,
        LanguageProvider $languageProvider
    ) {
        $this->configManager = $configManager;
        $this->localizationManager = $localizationManager;
        $this->languageProvider = $languageProvider;
    }

    /**
     * @return array
     */
    public function getLanguageChoices()
    {
        return $this->languageProvider->getAvailableLanguages();
    }

    /**
     * @return array
     */
    public function getFormattingChoices()
    {
        return Intl::getLocaleBundle()->getLocaleNames($this->getSystemLanguage());
    }

    /**
     * @return array
     */
    public function getLocalizationChoices()
    {
        /** @var Localization[] $choices */
        $choices = $this->localizationManager->getLocalizations();
        $data = [];

        foreach ($choices as $choice) {
            $data[$choice->getId()] = $choice->getName();
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getSystemLanguage()
    {
        return $this->configManager->get('oro_locale.language');
    }
}
