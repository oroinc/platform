<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

/**
 * This translation strategy has only one fallback - to the default locale.
 */
class DefaultTranslationStrategy implements TranslationStrategyInterface
{
    const NAME = 'default';

    /**
     * @var LanguageProvider
     */
    protected $languageProvider;

    /** @var bool */
    protected $installed = false;

    private TranslationStrategyInterface $strategy;

    /**
     * @param LanguageProvider $languageProvider
     * @param bool          $installed
     */
    public function __construct(LanguageProvider $languageProvider, $installed = false)
    {
        $this->languageProvider = $languageProvider;
        $this->installed = (bool)$installed;
    }

    public function setStrategy(TranslationStrategyInterface $strategy): void
    {
        $this->strategy = $strategy;
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
