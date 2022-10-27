<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;

/**
 * This translation strategy has only one fallback - to the default locale.
 */
class DefaultTranslationStrategy implements TranslationStrategyInterface
{
    private TranslationStrategyInterface $strategy;
    private ApplicationState $applicationState;

    public function __construct(TranslationStrategyInterface $strategy, ApplicationState $applicationState)
    {
        $this->strategy = $strategy;
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
            $locales = $this->strategy->getLocaleFallbacks();
            $nestedDefaultLocale = $locales[Configuration::DEFAULT_LOCALE][Configuration::DEFAULT_LOCALE] ?? [];
            if ($nestedDefaultLocale) {
                unset($locales[Configuration::DEFAULT_LOCALE][Configuration::DEFAULT_LOCALE]);
                $locales[Configuration::DEFAULT_LOCALE] = array_merge(
                    [Configuration::DEFAULT_LOCALE => []],
                    $nestedDefaultLocale,
                    $locales[Configuration::DEFAULT_LOCALE]
                );
            }
        } else {
            $locales = [
                Configuration::DEFAULT_LOCALE => []
            ];
        }

        return $locales;
    }
}
