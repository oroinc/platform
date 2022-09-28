<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFormatConverterTestCase extends \PHPUnit\Framework\TestCase
{
    protected const LOCALE_EN = 'en';
    protected const LOCALE_RU = 'ru';
    protected const LOCALE_AR = 'ar';
    protected const LOCALE_PT_BR = 'pt_BR';
    protected const LOCALE_ZH_CN = 'zh_CN';

    /** @var DateTimeFormatConverterInterface */
    protected $converter;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    protected $formatter;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected array $localFormatMap = [
        [null, null, self::LOCALE_EN, null, "MMM d, y h:mm a"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM, self::LOCALE_EN, null, "MMMM d, y h:mm:ss a"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::NONE, self::LOCALE_EN, null, "MMMM d, y"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, self::LOCALE_EN, null, "MMM d, y h:mm a"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, self::LOCALE_EN, null, "MMM d, y"],
        [null, \IntlDateFormatter::NONE, self::LOCALE_EN, null, "MMM d, y"],
        [\IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM, self::LOCALE_EN, null, "h:mm:ss a"],
        [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT, self::LOCALE_EN, null, "h:mm a"],
        [\IntlDateFormatter::NONE, null, self::LOCALE_EN, null, "h:mm a"],

        [null, null, self::LOCALE_RU, null, "dd.MM.yyyy H:mm"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM, self::LOCALE_RU, null, "d MMMM y 'г.' H:mm:ss"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::NONE, self::LOCALE_RU, null, "d MMMM y 'г.'"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, self::LOCALE_RU, null, "dd.MM.yyyy H:mm"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, self::LOCALE_RU, null, "dd.MM.yyyy"],
        [null, \IntlDateFormatter::NONE, self::LOCALE_RU, null, "dd.MM.yyyy"],
        [\IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM, self::LOCALE_RU, null, "H:mm:ss"],
        [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT, self::LOCALE_RU, null, "H:mm"],
        [\IntlDateFormatter::NONE, null, self::LOCALE_RU, null, "H:mm"],

        [null, null, self::LOCALE_AR, null, "d MMMM y h:mm:ss"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM, self::LOCALE_AR, null, "d MMMM y h:mm:ss"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::NONE, self::LOCALE_AR, null, "d MMMM y"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, self::LOCALE_AR, null, "dd‏/MM‏/y h:mm"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, self::LOCALE_AR, null, "dd‏/MM‏/y"],
        [null, \IntlDateFormatter::NONE, self::LOCALE_AR, null, "d MMMM y"],
        [\IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM, self::LOCALE_AR, null, "h:mm:ss"],
        [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT, self::LOCALE_AR, null, "h:mm"],
        [\IntlDateFormatter::NONE, null, self::LOCALE_AR, null, "h:mm:ss"],

        [null, null, self::LOCALE_PT_BR, null, "d 'de' MMMM 'de' y HH:mm:ss"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM, self::LOCALE_PT_BR, null, "d 'de' MMMM 'de' y HH:mm:ss"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::NONE, self::LOCALE_PT_BR, null, "d 'de' MMMM 'de' y"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, self::LOCALE_PT_BR, null, "d 'de' MMM 'de' y HH:mm"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, self::LOCALE_PT_BR, null, "d 'de' MMM 'de' y"],
        [null, \IntlDateFormatter::NONE, self::LOCALE_PT_BR, null, "d 'de' MMMM 'de' y"],
        [\IntlDateFormatter::NONE, \IntlDateFormatter::MEDIUM, self::LOCALE_PT_BR, null, 'HH:mm:ss'],
        [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT, self::LOCALE_PT_BR, null, 'HH:mm'],
        [\IntlDateFormatter::NONE, null, self::LOCALE_PT_BR, null, 'HH:mm:ss'],
        [null, null, self::LOCALE_ZH_CN, null, 'y年M月d日 HH:mm'],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::MEDIUM, self::LOCALE_ZH_CN, null, "y年M月d日 HH:mm:ss"],
        [\IntlDateFormatter::LONG, \IntlDateFormatter::NONE, self::LOCALE_ZH_CN, null, "y年M月d日"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, self::LOCALE_ZH_CN, null, "y年M月d日 HH:mm"],
        [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, self::LOCALE_ZH_CN, null, "y年M月d日"],
    ];

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->formatter->expects(self::any())
            ->method('getPattern')
            ->willReturnMap($this->localFormatMap);

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($one, $two, $tree, $locale) {
                if ($locale === self::LOCALE_EN) {
                    return 'MMM d';
                }

                if ($locale === self::LOCALE_RU) {
                    return 'd.MMM';
                }

                return '';
            });

        $this->converter = $this->createFormatConverter();
    }

    abstract protected function createFormatConverter(): DateTimeFormatConverterInterface;

    /**
     * @dataProvider getDateFormatDataProvider
     */
    public function testGetDateFormat(string $expected, ?int $dateFormat, string $locale): void
    {
        self::assertEquals($expected, $this->converter->getDateFormat($dateFormat, $locale));
    }

    abstract public function getDateFormatDataProvider(): array;

    /**
     * @dataProvider getTimeFormatDataProvider
     */
    public function testGetTimeFormat(string $expected, ?int $timeFormat, string $locale): void
    {
        self::assertEquals($expected, $this->converter->getTimeFormat($timeFormat, $locale));
    }

    abstract public function getTimeFormatDataProvider(): array;

    /**
     * @dataProvider getDateTimeFormatDataProvider
     */
    public function testGetDateTimeFormat(string $expected, ?int $dateFormat, ?int $timeFormat, string $locale): void
    {
        self::assertEquals($expected, $this->converter->getDateTimeFormat($dateFormat, $timeFormat, $locale));
    }

    abstract public function getDateTimeFormatDataProvider(): array;

    /**
     * @dataProvider getDateFormatDayProvider
     */
    public function testGetDayFormat(string $expected, string $locale): void
    {
        self::assertEquals($expected, $this->converter->getDayFormat($locale));
    }

    abstract public function getDateFormatDayProvider(): array;
}
