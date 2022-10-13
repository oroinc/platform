<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Returns the human readable language name based on language code and system language.
 */
class LanguageCodeFormatter
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var LocaleSettings */
    protected $localeSettings;

    public function __construct(TranslatorInterface $translator, LocaleSettings $localeSettings)
    {
        $this->translator = $translator;
        $this->localeSettings = $localeSettings;
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

        try {
            return Languages::getName($code, $this->localeSettings->getLanguage());
        } catch (MissingResourceException $e) {
            return $code;
        }
    }

    /**
     * @param string $code
     * @return string
     */
    public function formatLocale($code)
    {
        if (!$code) {
            return $this->translator->trans('N/A');
        }

        $lang = $this->localeSettings->getLanguage();

        return Locales::exists($code) ? Locales::getName($code, $lang) : $code;
    }
}
