<?php

namespace Oro\Bundle\LocaleBundle\Formatter;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
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

        $lang = $this->localeSettings->getLanguage();

        return Languages::exists($code) ?
            Languages::getName($code, $lang) :
            $this->formatNotExistsCode(Languages::class, $code, $lang);
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

        return Locales::exists($code) ?
            Locales::getName($code, $lang) :
            $this->formatNotExistsCode(Locales::class, $code, $lang);
    }

    /**
     * Format partially intl supported locales/languages to the human readable format
     *
     * en_plastimo => English Plastimo
     * en_CA_plastimo => English (Canada) Plastimo
     */
    private function formatNotExistsCode(string $resource, string $code, string $lang): string
    {
        $pieces = explode('_', $code);
        $piecesAmount = \count($pieces);

        if (1 === $piecesAmount) {
            return $code;
        }

        if ($piecesAmount > 1) {
            for ($i = $piecesAmount - 1; $i > 0; --$i) {
                $partialCode = implode('_', array_slice($pieces, 0, $i));
                if ($resource::exists($partialCode)) {
                    return $resource::getName($partialCode, $lang)
                        . ' ' . ucwords(implode(' ', array_slice($pieces, $i)));
                }
            }
        }

        return $code;
    }
}
