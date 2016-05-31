<?php

namespace Oro\Bundle\LocaleBundle\Twig;

use Oro\Bundle\LocaleBundle\Formatter\FormattingCodeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;

class LocalizationExtension extends \Twig_Extension
{
    const NAME = 'oro_locale_localization';

    /**
     * @var LanguageCodeFormatter
     */
    protected $languageCodeFormatter;

    /**
     * @var FormattingCodeFormatter
     */
    protected $formattingCodeFormatter;

    /**
     * @param LanguageCodeFormatter $languageCodeFormatter
     * @param FormattingCodeFormatter $formattingCodeFormatter
     */
    public function __construct(
        LanguageCodeFormatter $languageCodeFormatter,
        FormattingCodeFormatter $formattingCodeFormatter
    ) {
        $this->languageCodeFormatter = $languageCodeFormatter;
        $this->formattingCodeFormatter = $formattingCodeFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'oro_language_code_title',
                [$this, 'getLanguageTitleByCode'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFilter(
                'oro_formatting_code_title',
                [$this, 'getFormattingTitleByCode'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param string $code
     * @return string
     */
    public function getLanguageTitleByCode($code)
    {
        return $this->languageCodeFormatter->format($code);
    }

    /**
     * @param string $code
     * @return string
     */
    public function getFormattingTitleByCode($code)
    {
        return $this->formattingCodeFormatter->format($code);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
