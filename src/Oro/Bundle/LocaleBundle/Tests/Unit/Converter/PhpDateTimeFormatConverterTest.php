<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;
use Oro\Bundle\LocaleBundle\Converter\PhpDateTimeFormatConverter;

class PhpDateTimeFormatConverterTest extends AbstractFormatConverterTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createFormatConverter(): DateTimeFormatConverterInterface
    {
        return new PhpDateTimeFormatConverter($this->formatter, $this->translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatDataProvider(): array
    {
        return [
            'en default' => ['M j, Y', null, self::LOCALE_EN],
            'en custom' => ['F j, Y', \IntlDateFormatter::LONG, self::LOCALE_EN],
            'ru default' => ['d.m.Y', null, self::LOCALE_RU],
            'ru custom' => ['j F Y г.', \IntlDateFormatter::LONG, self::LOCALE_RU],
            'ar default' => ['j F Y', null, self::LOCALE_AR],
            'ar custom' => ['d‏/m‏/Y', \IntlDateFormatter::MEDIUM, self::LOCALE_AR],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeFormatDataProvider(): array
    {
        return [
            'en default' => ['g:i A', null, self::LOCALE_EN],
            'en custom' => ['g:i:s A', \IntlDateFormatter::MEDIUM, self::LOCALE_EN],
            'ru default' => ['G:i', null, self::LOCALE_RU],
            'ru custom' => ['G:i:s', \IntlDateFormatter::MEDIUM, self::LOCALE_RU],
            'ar default' => ['g:i:s', null, self::LOCALE_AR],
            'ar custom' => ['g:i', \IntlDateFormatter::SHORT, self::LOCALE_AR],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTimeFormatDataProvider(): array
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
            'ar default' => ['j F Y g:i:s', null, null, self::LOCALE_AR],
            'ar custom' => [
                'd‏/m‏/Y g:i',
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::SHORT,
                self::LOCALE_AR
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatDayProvider(): array
    {
        return [
            'en default' => ['M j', self::LOCALE_EN],
            'ru default' => ["d.m", self::LOCALE_RU],
            'ar default' => ["d‏/m", self::LOCALE_AR],
        ];
    }
}
