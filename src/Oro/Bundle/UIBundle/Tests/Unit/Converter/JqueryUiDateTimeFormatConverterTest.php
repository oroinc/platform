<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Tests\Unit\Converter\AbstractFormatConverterTestCase;
use Oro\Bundle\UIBundle\Converter\JqueryUiDateTimeFormatConverter;

class JqueryUiDateTimeFormatConverterTest extends AbstractFormatConverterTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createFormatConverter()
    {
        return new JqueryUiDateTimeFormatConverter($this->formatter, $this->translator);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatDataProvider()
    {
        return [
            'en default' => ["M d, yy", null, self::LOCALE_EN],
            'en custom' => ["MM d, yy", \IntlDateFormatter::LONG, self::LOCALE_EN],
            'ru default' => ["dd.mm.yy", null, self::LOCALE_RU],
            'ru custom' => ["d MM yy г.", \IntlDateFormatter::LONG, self::LOCALE_RU],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeFormatDataProvider()
    {
        return [
            'en default' => ["h:mm TT", null, self::LOCALE_EN],
            'en custom' => ["h:mm:ss TT", \IntlDateFormatter::MEDIUM, self::LOCALE_EN],
            'ru default' => ["H:mm", null, self::LOCALE_RU],
            'ru custom' => ["H:mm:ss", \IntlDateFormatter::MEDIUM, self::LOCALE_RU],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatDayProvider()
    {
        return [
            'en default' => ["M d", self::LOCALE_EN],
            'ru default' => ["dd.mm", self::LOCALE_RU],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormatDataProvider()
    {
        return [
            'en default' => ["M d, yy h:mm TT", null, null, self::LOCALE_EN],
            'en custom' => [
                "MM d, yy h:mm:ss TT",
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_EN
            ],
            'ru default' => ["dd.mm.yy H:mm", null, null, self::LOCALE_RU],
            'ru custom' => [
                "d MM yy г. H:mm:ss",
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_RU
            ],
        ];
    }
}
