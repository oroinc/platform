<?php

namespace Oro\Bundle\TranslationBundle\Strategy;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class DefaultTranslationStrategy implements TranslationStrategyInterface
{
    const NAME = 'default';

    /** @var ConfigManager */
    protected $cm;

    /** @var bool */
    protected $installed = false;

    /**
     * @param LocaleSettings $localeSettings
     * @param bool           $installed
     */
    public function __construct(ConfigManager $cm, $installed = false)
    {
        $this->cm = $cm;
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
        $locales = [];
        if ($this->installed) {
            $installedLocales = (array)$this->cm->get(TranslationStatusInterface::CONFIG_KEY);
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
