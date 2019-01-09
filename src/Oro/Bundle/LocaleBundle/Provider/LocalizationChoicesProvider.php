<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Symfony\Component\Intl\Intl;

/**
 * Provides lists of languages and formatting codes translated on current system language.
 */
class LocalizationChoicesProvider
{
    /** @var LocaleSettings */
    protected $localeSettings;

    /** @var LanguageCodeFormatter */
    protected $languageFormatter;

    /** @var LanguageProvider */
    protected $languageProvider;

    /** @var LocalizationManager */
    protected $localizationManager;

    /**
     * @param LocaleSettings $localeSettings
     * @param LanguageCodeFormatter $languageFormatter
     * @param LanguageProvider $languageProvider
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        LocaleSettings $localeSettings,
        LanguageCodeFormatter $languageFormatter,
        LanguageProvider $languageProvider,
        LocalizationManager $localizationManager
    ) {
        $this->localeSettings = $localeSettings;
        $this->languageFormatter = $languageFormatter;
        $this->languageProvider = $languageProvider;
        $this->localizationManager = $localizationManager;
    }

    /**
     * @param bool $onlyEnabled
     *
     * @return array
     */
    public function getLanguageChoices($onlyEnabled = false)
    {
        $result = [];

        foreach ($this->languageProvider->getLanguages($onlyEnabled) as $language) {
            $result[$this->languageFormatter->formatLocale($language->getCode())] = $language->getId();
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getFormattingChoices()
    {
        return array_flip(Intl::getLocaleBundle()->getLocaleNames($this->getSystemLanguage()));
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
            $data[$choice->getName()] = $choice->getId();
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getSystemLanguage()
    {
        return $this->localeSettings->getLanguage();
    }
}
