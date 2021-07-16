<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Used to format the formatting code and the system language to the human readable locale name.
 */
class FormattingCodeFormatter
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

        return Locales::exists($code) ? Locales::getName($code, $this->localeSettings->getLanguage()) : $code;
    }
}
