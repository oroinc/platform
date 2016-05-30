<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class LocaleCodeFormatter
{
    const CONFIG_KEY_DEFAULT_LANGUAGE = 'oro_locale.language';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param TranslatorInterface $translator
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
    public function formatLocaleCode($code)
    {
        if (!$code) {
            return $this->translator->trans('N/A');
        }

        $name = Intl::getLocaleBundle()->getLocaleName(
            $code,
            $this->configManager->get(self::CONFIG_KEY_DEFAULT_LANGUAGE)
        );

        return $name ?: $code;
    }
}
