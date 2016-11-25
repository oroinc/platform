<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

class DefaultTranslationStrategy implements TranslationStrategyInterface
{
    const NAME = 'default';

    /**
     * @var LanguageProvider
     */
    protected $languageProvider;

    /** @var bool */
    protected $installed = false;

    /**
     * @param LanguageProvider $languageProvider
     * @param bool          $installed
     */
    public function __construct(LanguageProvider $languageProvider, $installed = false)
    {
        $this->languageProvider = $languageProvider;
        $this->installed = (bool)$installed;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocaleFallbacks()
    {
        // default strategy has only one fallback to default locale
        if ($this->installed) {
            $locales = [];
            $installedLocales = $this->languageProvider->getAvailableLanguages();
            foreach ($installedLocales as $code => $installedLocale) {
                $locales[Configuration::DEFAULT_LOCALE][$code] = [];
            }
        } else {
            $locales = [
                Configuration::DEFAULT_LOCALE => []
            ];
        }

        return $locales;
    }
}
