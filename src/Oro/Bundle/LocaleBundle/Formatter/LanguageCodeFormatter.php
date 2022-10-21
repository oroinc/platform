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

        $lang = $this->localeSettings->getLanguage();
        try {
            return $this->postProcessLanguageName(Languages::getName($code, $lang), $code);
        } catch (MissingResourceException $e) {
            return $code;
        }
    }

    /**
     * If its custom language code we should append custom code suffix
     *
     * en_plastimo    -> English Plastimo
     * en_CA_plastimo -> Canadian English Plastimo
     */
    private function postProcessLanguageName(string $languageName, string $code): string
    {
        $codePieces = explode('_', $code);
        $diff = \count($codePieces) - \count(explode(' ', $languageName));

        if ($diff > 0) {
            $languageName .= ' '.ucwords(implode(' ', array_slice($codePieces, -$diff)));
        }

        return $languageName;
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
            $this->formatNotExistsLocaleCode($code, $lang);
    }

    /**
     * Format partially intl supported locale to the human readable format
     *
     * en_plastimo => English Plastimo
     * en_CA_plastimo => English (Canada) Plastimo
     */
    private function formatNotExistsLocaleCode(string $code, string $lang): string
    {
        $pieces = explode('_', $code);
        $piecesAmount = \count($pieces);

        if (1 === $piecesAmount) {
            return $code;
        }

        if ($piecesAmount > 1) {
            for ($i = $piecesAmount - 1; $i > 0; --$i) {
                $partialCode = implode('_', array_slice($pieces, 0, $i));
                if (Locales::exists($partialCode)) {
                    return Locales::getName($partialCode, $lang)
                        . ' ' . ucwords(implode(' ', array_slice($pieces, $i)));
                }
            }
        }

        return $code;
    }
}
