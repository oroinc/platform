<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;
use Oro\Bundle\LocaleBundle\Converter\IntlDateTimeFormatConverter;

class IntlDateTimeFormatConverterTest extends AbstractFormatConverterTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createFormatConverter(): DateTimeFormatConverterInterface
    {
        return new IntlDateTimeFormatConverter($this->formatter, $this->translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatDataProvider(): array
    {
        return [
            'en default' => ["MMM d, y", null, self::LOCALE_EN],
            'en custom' => ["MMMM d, y", \IntlDateFormatter::LONG, self::LOCALE_EN],
            'ru default' => ["dd.MM.yyyy", null, self::LOCALE_RU],
            'ru custom' => ["d MMMM y 'г.'", \IntlDateFormatter::LONG, self::LOCALE_RU],
            'ar default' => ['d MMMM y', null, self::LOCALE_AR],
            'ar custom' => ['dd‏/MM‏/y', \IntlDateFormatter::MEDIUM, self::LOCALE_AR],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeFormatDataProvider(): array
    {
        return [
            'en default' => ["h:mm a", null, self::LOCALE_EN],
            'en custom' => ["h:mm:ss a", \IntlDateFormatter::MEDIUM, self::LOCALE_EN],
            'ru default' => ["H:mm", null, self::LOCALE_RU],
            'ru custom' => ["H:mm:ss", \IntlDateFormatter::MEDIUM, self::LOCALE_RU],
            'ar default' => ['h:mm:ss', null, self::LOCALE_AR],
            'ar custom' => ['h:mm', \IntlDateFormatter::SHORT, self::LOCALE_AR],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDateTimeFormatDataProvider(): array
    {
        return [
            'en default' => ["MMM d, y h:mm a", null, null, self::LOCALE_EN],
            'en custom' => [
                "MMMM d, y h:mm:ss a",
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_EN,
            ],
            'ru default' => ["dd.MM.yyyy H:mm", null, null, self::LOCALE_RU],
            'ru custom'  => [
                "d MMMM y 'г.' H:mm:ss",
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_RU
            ],
            'ar default' => ['d MMMM y h:mm:ss', null, null, self::LOCALE_AR],
            'ar custom' => [
                'dd‏/MM‏/y h:mm',
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::SHORT,
                self::LOCALE_AR,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatDayProvider(): array
    {
        return [
            'en default' => ["MMM d", self::LOCALE_EN],
            'ru default' => ["dd.MM", self::LOCALE_RU],
            'ar default' => ["dd‏/MM", self::LOCALE_AR],
        ];
    }
}
