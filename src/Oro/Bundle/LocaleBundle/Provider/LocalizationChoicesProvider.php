<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;

class LocalizationChoicesProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var LanguageCodeFormatter */
    protected $languageFormatter;

    /** @var FormattingCodeFormatter */
    protected $formattingFormatter;

    /** @var LocalizationManager */
    protected $localizationManager;

    /**
     * @param ConfigManager $configManager
     * @param LanguageCodeFormatter $languageFormatter
     * @param FormattingCodeFormatter $formattingFormatter
     * @param LocalizationManager $localizationManager
     */
    public function __construct(
        ConfigManager $configManager,
        LanguageCodeFormatter $languageFormatter,
        FormattingCodeFormatter $formattingFormatter,
        LocalizationManager $localizationManager
    ) {
        $this->configManager = $configManager;
        $this->languageFormatter = $languageFormatter;
        $this->formattingFormatter = $formattingFormatter;
        $this->localizationManager = $localizationManager;
    }

    /**
     * @return array
     */
    public function getLanguageChoices()
    {
        return Intl::getLanguageBundle()->getLanguageNames($this->getSystemLanguage());
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
