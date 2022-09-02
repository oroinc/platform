<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

/**
 * This translation strategy has only one fallback - to the default locale.
 */
class DefaultTranslationStrategy implements TranslationStrategyInterface
{
    private LanguageProvider $languageProvider;
    private ApplicationState $applicationState;

    public function __construct(LanguageProvider $languageProvider, ApplicationState $applicationState)
    {
        $this->languageProvider = $languageProvider;
        $this->applicationState = $applicationState;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'default';
    }

    /**
     * {@inheritDoc}
     */
    public function getLocaleFallbacks()
    {
        // default strategy has only one fallback to default locale
        if ($this->applicationState->isInstalled()) {
            $locales = [];
            $codes = $this->languageProvider->getAvailableLanguageCodes();
            foreach ($codes as $code) {
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
