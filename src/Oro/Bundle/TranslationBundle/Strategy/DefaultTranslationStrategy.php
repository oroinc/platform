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
    const NAME = 'default';

    /** @var LanguageProvider */
    protected $languageProvider;

    private ApplicationState $applicationState;

    /**
     * @param LanguageProvider $languageProvider
     * @param ApplicationState $applicationState
     */
    public function __construct(LanguageProvider $languageProvider, ApplicationState $applicationState)
    {
        $this->languageProvider = $languageProvider;
        $this->applicationState = $applicationState;
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
        if ($this->applicationState->isInstalled()) {
            $locales = [];
            foreach ($this->languageProvider->getAvailableLanguageCodes() as $code) {
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
