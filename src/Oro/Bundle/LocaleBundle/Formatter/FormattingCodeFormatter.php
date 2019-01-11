<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Used to format the formatting code and the system language to the human readable locale name.
 */
class FormattingCodeFormatter
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var LocaleSettings */
    protected $localeSettings;

    /**
     * @param TranslatorInterface $translator
     * @param LocaleSettings $localeSettings
     */
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

        $name = Intl::getLocaleBundle()->getLocaleName(
            $code,
            $this->localeSettings->getLanguage()
        );

        return $name ?: $code;
    }
}
