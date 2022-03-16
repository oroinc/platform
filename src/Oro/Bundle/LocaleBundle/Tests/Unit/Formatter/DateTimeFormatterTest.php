<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DateTimeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var DateTimeFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturn('MMM d');

        $this->formatter = new DateTimeFormatter($this->localeSettings, $translator);
    }

    public function testEmptyDate()
    {
        $this->assertEquals(null, $this->formatter->format(null));
        $this->assertEquals(null, $this->formatter->formatTime(null));
        $this->assertEquals(null, $this->formatter->formatDate(null));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @dataProvider formatDataProvider
     */
    public function testFormat(
        int $expectedDateType,
        int $expectedTimeType,
        \DateTime $expectedDate,
        \DateTime|string|int $date,
        int|string|null $dateType,
        int|string|null $timeType,
        ?string $locale,
        ?string $timeZone,
        string $language,
        string $defaultLocale = null,
        string $defaultTimeZone = null
    ) {
        $this->localeSettings->expects($this->once())
            ->method('getLanguage')
            ->willReturn($language);
        $methodCalls = 1;
        if ($defaultLocale) {
            $methodCalls++;
            $this->localeSettings->expects($this->once())
                ->method('getLocale')
                ->willReturn($defaultLocale);
        }
        if ($defaultTimeZone) {
            $methodCalls++;
            $this->localeSettings->expects($this->once())
                ->method('getTimeZone')
                ->willReturn($defaultTimeZone);
        }
        $this->localeSettings->expects($this->exactly($methodCalls))
            ->method($this->anything());

        $pattern = $this->getPattern($locale ? : $defaultLocale, $expectedDateType, $expectedTimeType);
        $formatter = $this->getFormatter($language, $timeZone ? : $defaultTimeZone, $pattern);
        $expected = $formatter->format((int)$expectedDate->format('U'));

        $this->assertEquals(
            $expected,
            $this->formatter->format($date, $dateType, $timeType, $locale, $timeZone)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formatDataProvider(): array
    {
        return [
            'full_format' => [
                'expectedDateType' => \IntlDateFormatter::FULL,
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ],
            'full_format_default_locale_and_timezone' => [
                'expectedDateType' => \IntlDateFormatter::FULL,
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => null,
                'timeZone' => null,
                'language' => 'en_US',
                'defaultLocale' => 'en_US',
                'defaultTimeZone' => 'America/Los_Angeles',
            ],
            'full_format_english_locale_french_language' => [
                'expectedDateType' => \IntlDateFormatter::FULL,
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr_FR',
            ],
            'string_date' => [
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'UTC'),
                'date' => '2014-01-01 00:00:00',
                'dateType' => \IntlDateFormatter::SHORT,
                'timeType' => \IntlDateFormatter::SHORT,
                'locale' => 'en_CA',
                'timeZone' => 'Europe/Athens',
                'language' => 'en_CA',
            ],
            'string_date_with_timezone' => [
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2013-12-31 22:00:00', 'UTC'),
                'date' => '2014-01-01 00:00:00+2',
                'dateType' => \IntlDateFormatter::SHORT,
                'timeType' => \IntlDateFormatter::SHORT,
                'locale' => 'en_CA',
                'timeZone' => 'Europe/Athens',
                'language' => 'en_CA',
            ],
            'integer_date' => [
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2014-01-01 08:00:00', 'UTC'),
                'date' => 1388563200,
                'dateType' => \IntlDateFormatter::SHORT,
                'timeType' => \IntlDateFormatter::SHORT,
                'locale' => 'en_CA',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_CA',
            ],
            'short_format_and_text_date_types' => [
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => 'short',
                'timeType' => 'short',
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ],
            'long_date_without_time' => [
                'expectedDateType' => \IntlDateFormatter::LONG,
                'expectedTimeType' => \IntlDateFormatter::NONE,
                'expectedDate' => $this->createDateTime('2014-01-01 02:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::LONG,
                'timeType' => \IntlDateFormatter::NONE,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr_FR',
            ],
            'long_date_without_time_french_locale_russian_language' => [
                'expectedDateType' => \IntlDateFormatter::LONG,
                'expectedTimeType' => \IntlDateFormatter::NONE,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::LONG,
                'timeType' => \IntlDateFormatter::NONE,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'ru_RU',
            ],
            'default_date_and_time_type' => [
                'expectedDateType' => \IntlDateFormatter::MEDIUM,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => null,
                'timeType' => null,
                'locale' => 'en_CA',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_CA',
            ],
        ];
    }

    /**
     * @dataProvider formatDateDataProvider
     */
    public function testFormatDate(
        int $expectedDateType,
        \DateTime $date,
        int|string|null $dateType,
        ?string $locale,
        ?string $timeZone,
        string $language,
        string $defaultLocale = null,
        string $defaultTimeZone = null
    ) {
        $this->localeSettings->expects($this->once())
            ->method('getLanguage')
            ->willReturn($language);
        $methodCalls = 1;
        if ($defaultLocale) {
            $this->localeSettings->expects($this->once())
                ->method('getLocale')
                ->willReturn($defaultLocale);
            $methodCalls++;
        }
        if ($defaultTimeZone) {
            $this->localeSettings->expects($this->once())
                ->method('getTimeZone')
                ->willReturn($defaultTimeZone);
            $methodCalls++;
        }
        $this->localeSettings->expects($this->exactly($methodCalls))
            ->method($this->anything());

        $pattern = $this->getPattern($locale ? : $defaultLocale, $expectedDateType, \IntlDateFormatter::NONE);
        $formatter = $this->getFormatter($language, $timeZone ? : $defaultTimeZone, $pattern);
        $expected = $formatter->format((int)$date->format('U'));

        $this->assertEquals(
            $expected,
            $this->formatter->formatDate($date, $dateType, $locale, $timeZone)
        );
    }

    public function formatDateDataProvider(): array
    {
        return [
            'full_date' => [
                'expectedDateType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ],
            'full_date_default_locale_and_timezone' => [
                'expectedDateType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'locale' => null,
                'timeZone' => null,
                'language' => 'en_US',
                'defaultLocale' => 'en_US',
                'defaultTimeZone' => 'America/Los_Angeles',
            ],
            'full_date_object' => [
                'expectedDateType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ],
            'short_date_and_text_date_type' => [
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => 'short',
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ],
            'long_date' => [
                'expectedDateType' => \IntlDateFormatter::LONG,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::LONG,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr_FR',
            ],
            'long_date_french_locale_english_language' => [
                'expectedDateType' => \IntlDateFormatter::LONG,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::LONG,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en',
            ],
            'default_date_type' => [
                'expectedDateType' => \IntlDateFormatter::MEDIUM,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => null,
                'locale' => 'en_CA',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_CA',
            ],
        ];
    }

    /**
     * @dataProvider formatTimeDataProvider
     */
    public function testFormatTime(
        $expectedTimeType,
        \DateTime $date,
        int|string|null $timeType,
        ?string $locale,
        ?string $timeZone,
        string $language,
        string $defaultLocale = null,
        string $defaultTimeZone = null
    ) {
        $this->localeSettings->expects($this->once())
            ->method('getLanguage')
            ->willReturn($language);
        $methodCalls = 1;
        if ($defaultLocale) {
            $this->localeSettings->expects($this->once())
                ->method('getLocale')
                ->willReturn($defaultLocale);
            $methodCalls++;
        }
        if ($defaultTimeZone) {
            $this->localeSettings->expects($this->once())
                ->method('getTimeZone')
                ->willReturn($defaultTimeZone);
            $methodCalls++;
        }
        $this->localeSettings->expects($this->exactly($methodCalls))
            ->method($this->anything());

        $pattern = $this->getPattern($locale ? : $defaultLocale, \IntlDateFormatter::NONE, $expectedTimeType);
        $formatter = $this->getFormatter($language, $timeZone ? : $defaultTimeZone, $pattern);
        $expected = $formatter->format((int)$date->format('U'));

        $this->assertEquals(
            $expected,
            $this->formatter->formatTime($date, $timeType, $locale, $timeZone)
        );
    }

    public function formatTimeDataProvider(): array
    {
        return [
            'full_date' => [
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ],
            'full_date_default_locale_and_timezone' => [
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => null,
                'timeZone' => null,
                'language' => 'en_US',
                'defaultLocale' => 'en_US',
                'defaultTimeZone' => 'America/Los_Angeles',
            ],
            'full_date_english_locale_french_language' => [
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr',
            ],
            'short_date_and_text_date_type' => [
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => 'short',
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ],
            'long_time' => [
                'expectedTimeType' => \IntlDateFormatter::LONG,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => \IntlDateFormatter::LONG,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr_FR',
            ],
            'default_date_type' => [
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => null,
                'locale' => 'en_CA',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_CA',
            ],
        ];
    }

    /**
     * @dataProvider formatDayDataProvider
     */
    public function testFormatDay(
        \DateTime $date,
        int $dateType,
        ?string $locale,
        ?string $timeZone,
        string $language,
        string $year,
        ?string $defaultLocale = null,
        ?string $defaultTimeZone = null
    ) {
        $this->localeSettings->expects($this->any())
            ->method('getLanguage')
            ->willReturn($language);
        if ($defaultLocale) {
            $this->localeSettings->expects($this->any())
                ->method('getLocale')
                ->willReturn($defaultLocale);
        }
        if ($defaultTimeZone) {
            $this->localeSettings->expects($this->any())
                ->method('getTimeZone')
                ->willReturn($defaultTimeZone);
        }

        $actual = $this->formatter->formatDay($date, $dateType, $locale, $timeZone);
        $this->assertStringNotContainsString("'", $actual);
        $this->assertStringNotContainsString(',', $actual);
        $this->assertStringNotContainsString($year, $actual);
    }

    public function formatDayDataProvider(): array
    {
        return [
            [
                'date' => $this->createDateTime('2032-02-03 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::MEDIUM,
                'locale' => 'ru_RU',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
                'year' => '32',
            ],
            [
                'date' => $this->createDateTime('2032-02-03 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::MEDIUM,
                'locale' => null,
                'timeZone' => null,
                'language' => 'ru_RU',
                'year' => '32',
                'defaultLocale' => 'en_US',
                'defaultTimeZone' => 'America/Los_Angeles',
            ],
        ];
    }

    private function createDateTime(string $date, string $timeZone): \DateTime
    {
        return new \DateTime($date, new \DateTimeZone($timeZone));
    }

    /**
     * @dataProvider getDatePatternDataProvider
     */
    public function testGetDatePattern(
        int $expectedDateType,
        int $expectedTimeType,
        int|string $dateType,
        int|string $timeType,
        string $locale
    ) {
        $expected = $this->getPattern($locale, $expectedDateType, $expectedTimeType);
        $this->assertEquals($expected, $this->formatter->getPattern($dateType, $timeType, $locale));
    }

    public function getDatePatternDataProvider(): array
    {
        return [
            [
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'en_US'
            ],
            [
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'fr_FR'
            ],
            [
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'full',
                'full',
                'fr_FR'
            ],
        ];
    }

    /**
     * @dataProvider getDateTimeNotModifiedDataProvider
     */
    public function testGetDateTimeReturnsNotModified(\DateTimeInterface $date)
    {
        $this->assertSame($date, $this->formatter->getDateTime($date));
    }

    public function getDateTimeNotModifiedDataProvider(): array
    {
        return [
            'DateTime' => [
                'date' => new \DateTime,
            ],
            'DateTimeImmutable' => [
                'date' => new \DateTimeImmutable(),
            ],
        ];
    }

    public function testGetDateTimeFromString()
    {
        $actual = $this->formatter->getDateTime('10 September 2000');
        $this->assertInstanceOf(\DateTime::class, $actual);
        $this->assertEquals('UTC', $actual->getTimezone()->getName());
    }

    public function testGetDateTimeFromInteger()
    {
        $actual = $this->formatter->getDateTime(1523028070);
        $this->assertInstanceOf(\DateTime::class, $actual);
        $this->assertEquals('UTC', $actual->getTimezone()->getName());
    }

    private function getFormatter(string $lang, string $timeZone, string $pattern): \IntlDateFormatter
    {
        return new \IntlDateFormatter(
            $lang,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $timeZone,
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );
    }

    private function getPattern(string $locale, int $dateType, int $timeType): string
    {
        $localeFormatter = new \IntlDateFormatter($locale, $dateType, $timeType, null, \IntlDateFormatter::GREGORIAN);

        return $localeFormatter->getPattern();
    }
}
