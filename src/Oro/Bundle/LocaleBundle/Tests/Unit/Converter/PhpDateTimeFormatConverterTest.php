<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\PhpDateTimeFormatConverter;

class PhpDateTimeFormatConverterTest extends AbstractFormatConverterTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createFormatConverter()
    {
        return new PhpDateTimeFormatConverter($this->formatter, $this->translator);
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatDataProvider()
    {
        return [
            'en default' => ['M j, Y', null, self::LOCALE_EN],
            'en custom' => ['F j, Y', \IntlDateFormatter::LONG, self::LOCALE_EN],
            'ru default' => ['d.m.Y', null, self::LOCALE_RU],
            'ru custom' => ['j F Y г.', \IntlDateFormatter::LONG, self::LOCALE_RU],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTimeFormatDataProvider()
    {
        return [
            'en default' => ['g:i A', null, self::LOCALE_EN],
            'en custom' => ['g:i:s A', \IntlDateFormatter::MEDIUM, self::LOCALE_EN],
            'ru default' => ['G:i', null, self::LOCALE_RU],
            'ru custom' => ['G:i:s', \IntlDateFormatter::MEDIUM, self::LOCALE_RU],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormatDataProvider()
    {
        return [
            'en default' => ['M j, Y g:i A', null, null, self::LOCALE_EN],
            'en custom' => [
                'F j, Y g:i:s A',
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_EN
            ],
            'ru default' => ['d.m.Y G:i', null, null, self::LOCALE_RU],
            'ru custom' => [
                'j F Y г. G:i:s',
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_RU
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDateFormatDayProvider()
    {
        return [
            'en default' => ['M j', self::LOCALE_EN],
            'ru default' => ["d.m", self::LOCALE_RU],
        ];
    }
}
