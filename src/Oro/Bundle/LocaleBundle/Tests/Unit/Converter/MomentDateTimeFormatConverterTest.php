<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;
use Oro\Bundle\LocaleBundle\Converter\MomentDateTimeFormatConverter;

class MomentDateTimeFormatConverterTest extends AbstractFormatConverterTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createFormatConverter(): DateTimeFormatConverterInterface
    {
        return new MomentDateTimeFormatConverter($this->formatter, $this->translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatDataProvider(): array
    {
        return [
            'en default' => ["MMM D, YYYY", null, self::LOCALE_EN],
            'en custom' => ["MMMM D, YYYY", \IntlDateFormatter::LONG, self::LOCALE_EN],
            'ru default' => ["DD.MM.YYYY", null, self::LOCALE_RU],
            'ru custom' => ["D MMMM YYYY [г.]", \IntlDateFormatter::LONG, self::LOCALE_RU],
            'ar default' => ['D MMMM YYYY', null, self::LOCALE_AR],
            'ar custom' => ['DD‏/MM‏/YYYY', \IntlDateFormatter::MEDIUM, self::LOCALE_AR],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeFormatDataProvider(): array
    {
        return [
            'en default' => ["h:mm A", null, self::LOCALE_EN],
            'en custom' => ["h:mm:ss A", \IntlDateFormatter::MEDIUM, self::LOCALE_EN],
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
            'en default' => ["MMM D, YYYY h:mm A", null, null, self::LOCALE_EN],
            'en custom' => [
                "MMMM D, YYYY h:mm:ss A",
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_EN,
            ],
            'ru default' => ["DD.MM.YYYY H:mm", null, null, self::LOCALE_RU],
            'ru custom' => [
                "D MMMM YYYY [г.] H:mm:ss",
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_RU,
            ],
            'ar default' => ['D MMMM YYYY h:mm:ss', null, null, self::LOCALE_AR],
            'ar custom' => [
                'DD‏/MM‏/YYYY h:mm',
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
            'en default' => ["MMM D", self::LOCALE_EN],
            'ru default' => ["DD.MM", self::LOCALE_RU],
            'ar default' => ["DD‏/MM", self::LOCALE_AR],
        ];
    }
}
