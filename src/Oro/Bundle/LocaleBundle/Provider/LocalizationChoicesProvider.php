<?php

namespace Oro\Bundle\LocaleBundle\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Intl\Intl;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;

class LocalizationChoicesProvider
{
    /** @var ObjectRepository */
    protected $repository;

    /** @var ConfigManager */
    protected $configManager;

    /** @var LanguageCodeFormatter */
    protected $languageFormatter;

    /** @var FormattingCodeFormatter */
    protected $formattingFormatter;

    /**
     * @param ObjectRepository $repository
     * @param ConfigManager $configManager
     * @param LanguageCodeFormatter $languageFormatter
     * @param FormattingCodeFormatter $formattingFormatter
     */
    public function __construct(
        ObjectRepository $repository,
        ConfigManager $configManager,
        LanguageCodeFormatter $languageFormatter,
        FormattingCodeFormatter $formattingFormatter
    ) {
        $this->repository = $repository;
        $this->configManager = $configManager;
        $this->languageFormatter = $languageFormatter;
        $this->formattingFormatter = $formattingFormatter;
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
        $choices = $this->repository->findBy([], ['name' => 'ASC']);
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
