<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class LanguageCodeFormatter
{
    const CONFIG_KEY_DEFAULT_LANGUAGE = 'oro_locale.language';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param TranslatorInterface $translator
     * @param ConfigManager $configManager
     */
    public function __construct(TranslatorInterface $translator, ConfigManager $configManager)
    {
        $this->translator = $translator;
        $this->configManager = $configManager;
    }

    /**
     * @param string $code
     * @return string
     */
    public function format($code)
    {
        if (!$code) {
            return $this->translator->trans('N/A');
        }

        $name = Intl::getLanguageBundle()->getLanguageName(
            $code,
            $this->configManager->get(self::CONFIG_KEY_DEFAULT_LANGUAGE)
        );

        return $name ?: $code;
    }
}
