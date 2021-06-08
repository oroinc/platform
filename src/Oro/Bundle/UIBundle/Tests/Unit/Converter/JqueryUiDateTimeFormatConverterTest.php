<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Converter\AbstractFormatConverterTestCase;
use Oro\Bundle\UIBundle\Converter\JqueryUiDateTimeFormatConverter;

class JqueryUiDateTimeFormatConverterTest extends AbstractFormatConverterTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createFormatConverter(): DateTimeFormatConverterInterface
    {
        return new JqueryUiDateTimeFormatConverter($this->formatter, $this->translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getDateFormatDataProvider(): array
    {
        return [
            'en default' => ['M d, yy', null, self::LOCALE_EN],
            'en custom' => ['MM d, yy', \IntlDateFormatter::LONG, self::LOCALE_EN],
            'ru default' => ['dd.mm.yy', null, self::LOCALE_RU],
            'ru custom' => ["d MM yy 'г.'", \IntlDateFormatter::LONG, self::LOCALE_RU],
            'pt_BR default' => ["d 'de' MM 'de' yy", null, self::LOCALE_PT_BR],
            'pt_BR custom' => ["d 'de' MM 'de' yy", \IntlDateFormatter::LONG, self::LOCALE_PT_BR],
            'ar default' => ['d MM yy', null, self::LOCALE_AR],
            'ar custom' => ['dd‏/mm‏/yy', \IntlDateFormatter::MEDIUM, self::LOCALE_AR],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeFormatDataProvider(): array
    {
        return [
            'en default' => ['h:mm TT', null, self::LOCALE_EN],
            'en custom' => ['h:mm:ss TT', \IntlDateFormatter::MEDIUM, self::LOCALE_EN],
            'ru default' => ['H:mm', null, self::LOCALE_RU],
            'ru custom' => ['H:mm:ss', \IntlDateFormatter::MEDIUM, self::LOCALE_RU],
            'pt_BR default' => ['HH:mm:ss', null, self::LOCALE_PT_BR],
            'pt_BR custom' => ['HH:mm:ss', \IntlDateFormatter::MEDIUM, self::LOCALE_PT_BR],
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
            'en default' => ['M d, yy h:mm TT', null, null, self::LOCALE_EN],
            'en custom' => [
                'MM d, yy h:mm:ss TT',
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_EN,
            ],
            'ru default' => ['dd.mm.yy H:mm', null, null, self::LOCALE_RU],
            'ru custom' => [
                "d MM yy 'г.' H:mm:ss",
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_RU
            ],
            'pt_BR default' => ["d 'de' MM 'de' yy HH:mm:ss", null, null, self::LOCALE_PT_BR],
            'pt_BR custom' => [
                "d 'de' MM 'de' yy HH:mm:ss",
                \IntlDateFormatter::LONG,
                \IntlDateFormatter::MEDIUM,
                self::LOCALE_PT_BR
            ],
            'ar default' => ['d MM yy h:mm:ss', null, null, self::LOCALE_AR],
            'ar custom' => [
                'dd‏/mm‏/yy h:mm',
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
            'en default' => ['M d', self::LOCALE_EN],
            'ru default' => ['dd.mm', self::LOCALE_RU],
            'pt_BR default' => ["d 'de' M 'de'", self::LOCALE_PT_BR],
            'ar default' => ["dd‏/mm", self::LOCALE_AR],
        ];
    }
}
