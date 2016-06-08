<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;

class DefaultTranslationStrategy implements TranslationStrategyInterface
{
    const NAME = 'default';

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /** @var bool */
    protected $installed = false;

    /**
     * @param LocaleSettings $localeSettings
     * @param bool $installed
     */
    public function __construct(LocaleSettings $localeSettings, $installed = false)
    {
        $this->localeSettings = $localeSettings;
        $this->installed = (bool)$installed;
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
            $locales = [
                Configuration::DEFAULT_LOCALE => [
                    $this->localeSettings->getLanguage() => []
                ]
            ];
        } else {
            $locales = [
                Configuration::DEFAULT_LOCALE => []
            ];
        }

        return $locales;
    }
}
