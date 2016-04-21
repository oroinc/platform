<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class DefaultTranslationStrategy implements TranslationStrategyInterface
{
    const NAME = 'default';

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @param LocaleSettings $localeSettings
     */
    public function __construct(LocaleSettings $localeSettings)
    {
        $this->localeSettings = $localeSettings;
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
        // default strategy has only one locale and no fallbacks
        return [$this->localeSettings->getLocale() => []];
    }
}
