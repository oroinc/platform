<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Formatter;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DateTimeFormatterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $localeSettings;

    /**
     * @var DateTimeFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $translator->method('trans')
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
        $expectedDateType,
        $expectedTimeType,
        \DateTime $expectedDate,
        $date,
        $dateType,
        $timeType,
        $locale,
        $timeZone,
        $language,
        $defaultLocale = null,
        $defaultTimeZone = null
    ) {
        $this->localeSettings->expects($this->once())->method('getLanguage')->will($this->returnValue($language));
        $methodCalls = 1;
        if ($defaultLocale) {
            $methodCalls++;
            $this->localeSettings->expects($this->once())->method('getLocale')
                ->will($this->returnValue($defaultLocale));
        }
        if ($defaultTimeZone) {
            $methodCalls++;
            $this->localeSettings->expects($this->once())->method('getTimeZone')
                ->will($this->returnValue($defaultTimeZone));
        }
        $this->localeSettings->expects($this->exactly($methodCalls))->method($this->anything());

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
     * @return array
     */
    public function formatDataProvider()
    {
        return array(
            'full_format' => array(
                'expectedDateType' => \IntlDateFormatter::FULL,
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ),
            'full_format_default_locale_and_timezone' => array(
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
            ),
            'full_format_english_locale_french_language' => array(
                'expectedDateType' => \IntlDateFormatter::FULL,
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr_FR',
            ),
            'string_date' => array(
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'UTC'),
                'date' => '2014-01-01 00:00:00',
                'dateType' => \IntlDateFormatter::SHORT,
                'timeType' => \IntlDateFormatter::SHORT,
                'locale' => 'en_CA',
                'timeZone' => 'Europe/Athens',
                'language' => 'en_CA',
            ),
            'string_date_with_timezone' => array(
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2013-12-31 22:00:00', 'UTC'),
                'date' => '2014-01-01 00:00:00+2',
                'dateType' => \IntlDateFormatter::SHORT,
                'timeType' => \IntlDateFormatter::SHORT,
                'locale' => 'en_CA',
                'timeZone' => 'Europe/Athens',
                'language' => 'en_CA',
            ),
            'integer_date' => array(
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2014-01-01 08:00:00', 'UTC'),
                'date' => 1388563200,
                'dateType' => \IntlDateFormatter::SHORT,
                'timeType' => \IntlDateFormatter::SHORT,
                'locale' => 'en_CA',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_CA',
            ),
            'short_format_and_text_date_types' => array(
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => 'short',
                'timeType' => 'short',
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ),
            'long_date_without_time' => array(
                'expectedDateType' => \IntlDateFormatter::LONG,
                'expectedTimeType' => \IntlDateFormatter::NONE,
                'expectedDate' => $this->createDateTime('2014-01-01 02:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::LONG,
                'timeType' => \IntlDateFormatter::NONE,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr_FR',
            ),
            'long_date_without_time_french_locale_russian_language' => array(
                'expectedDateType' => \IntlDateFormatter::LONG,
                'expectedTimeType' => \IntlDateFormatter::NONE,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::LONG,
                'timeType' => \IntlDateFormatter::NONE,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'ru_RU',
            ),
            'default_date_and_time_type' => array(
                'expectedDateType' => \IntlDateFormatter::MEDIUM,
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'expectedDate' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => null,
                'timeType' => null,
                'locale' => 'en_CA',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_CA',
            ),
        );
    }

    /**
     * @dataProvider formatDateDataProvider
     */
    public function testFormatDate(
        $expectedDateType,
        \DateTime $date,
        $dateType,
        $locale,
        $timeZone,
        $language,
        $defaultLocale = null,
        $defaultTimeZone = null
    ) {
        $this->localeSettings->expects($this->once())->method('getLanguage')->will($this->returnValue($language));
        $methodCalls = 1;
        if ($defaultLocale) {
            $this->localeSettings->expects($this->once())->method('getLocale')
                ->will($this->returnValue($defaultLocale));
            $methodCalls++;
        }
        if ($defaultTimeZone) {
            $this->localeSettings->expects($this->once())->method('getTimeZone')
                ->will($this->returnValue($defaultTimeZone));
            $methodCalls++;
        }
        $this->localeSettings->expects($this->exactly($methodCalls))->method($this->anything());

        $pattern = $this->getPattern($locale ? : $defaultLocale, $expectedDateType, \IntlDateFormatter::NONE);
        $formatter = $this->getFormatter($language, $timeZone ? : $defaultTimeZone, $pattern);
        $expected = $formatter->format((int)$date->format('U'));

        $this->assertEquals(
            $expected,
            $this->formatter->formatDate($date, $dateType, $locale, $timeZone)
        );
    }

    public function formatDateDataProvider()
    {
        return array(
            'full_date' => array(
                'expectedDateType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ),
            'full_date_default_locale_and_timezone' => array(
                'expectedDateType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'locale' => null,
                'timeZone' => null,
                'language' => 'en_US',
                'defaultLocale' => 'en_US',
                'defaultTimeZone' => 'America/Los_Angeles',
            ),
            'full_date_object' => array(
                'expectedDateType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ),
            'short_date_and_text_date_type' => array(
                'expectedDateType' => \IntlDateFormatter::SHORT,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => 'short',
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ),
            'long_date' => array(
                'expectedDateType' => \IntlDateFormatter::LONG,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::LONG,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr_FR',
            ),
            'long_date_french_locale_english_language' => array(
                'expectedDateType' => \IntlDateFormatter::LONG,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => \IntlDateFormatter::LONG,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en',
            ),
            'default_date_type' => array(
                'expectedDateType' => \IntlDateFormatter::MEDIUM,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'dateType' => null,
                'locale' => 'en_CA',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_CA',
            ),
        );
    }

    /**
     * @dataProvider formatTimeDataProvider
     */
    public function testFormatTime(
        $expectedTimeType,
        \DateTime $date,
        $timeType,
        $locale,
        $timeZone,
        $language,
        $defaultLocale = null,
        $defaultTimeZone = null
    ) {
        $this->localeSettings->expects($this->once())->method('getLanguage')->will($this->returnValue($language));
        $methodCalls = 1;
        if ($defaultLocale) {
            $this->localeSettings->expects($this->once())->method('getLocale')
                ->will($this->returnValue($defaultLocale));
            $methodCalls++;
        }
        if ($defaultTimeZone) {
            $this->localeSettings->expects($this->once())->method('getTimeZone')
                ->will($this->returnValue($defaultTimeZone));
            $methodCalls++;
        }
        $this->localeSettings->expects($this->exactly($methodCalls))->method($this->anything());

        $pattern = $this->getPattern($locale ? : $defaultLocale, \IntlDateFormatter::NONE, $expectedTimeType);
        $formatter = $this->getFormatter($language, $timeZone ? : $defaultTimeZone, $pattern);
        $expected = $formatter->format((int)$date->format('U'));

        $this->assertEquals(
            $expected,
            $this->formatter->formatTime($date, $timeType, $locale, $timeZone)
        );
    }

    public function formatTimeDataProvider()
    {
        return array(
            'full_date' => array(
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ),
            'full_date_default_locale_and_timezone' => array(
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => null,
                'timeZone' => null,
                'language' => 'en_US',
                'defaultLocale' => 'en_US',
                'defaultTimeZone' => 'America/Los_Angeles',
            ),
            'full_date_english_locale_french_language' => array(
                'expectedTimeType' => \IntlDateFormatter::FULL,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => \IntlDateFormatter::FULL,
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr',
            ),
            'short_date_and_text_date_type' => array(
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => 'short',
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_US',
            ),
            'long_time' => array(
                'expectedTimeType' => \IntlDateFormatter::LONG,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => \IntlDateFormatter::LONG,
                'locale' => 'fr_FR',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'fr_FR',
            ),
            'default_date_type' => array(
                'expectedTimeType' => \IntlDateFormatter::SHORT,
                'date' => $this->createDateTime('2014-01-01 00:00:00', 'Europe/London'),
                'timeType' => null,
                'locale' => 'en_CA',
                'timeZone' => 'America/Los_Angeles',
                'language' => 'en_CA',
            ),
        );
    }

    /**
     * @param \DateTime $date
     * @param int       $dateType
     * @param string    $locale
     * @param string    $timeZone
     * @param string    $language
     * @param string    $year
     * @param string    $defaultLocale
     * @param string    $defaultTimeZone
     *
     * @dataProvider formatDayDataProvider
     */
    public function testFormatDay(
        \DateTime $date,
        $dateType,
        $locale,
        $timeZone,
        $language,
        $year,
        $defaultLocale = null,
        $defaultTimeZone = null
    ) {
        $this->localeSettings->expects($this->any())->method('getLanguage')->will($this->returnValue($language));
        if ($defaultLocale) {
            $this->localeSettings->expects($this->any())->method('getLocale')
                ->will($this->returnValue($defaultLocale));
        }
        if ($defaultTimeZone) {
            $this->localeSettings->expects($this->any())->method('getTimeZone')
                ->will($this->returnValue($defaultTimeZone));
        }

        $actual = $this->formatter->formatDay($date, $dateType, $locale, $timeZone);
        $this->assertStringNotContainsString("'", $actual);
        $this->assertStringNotContainsString(',', $actual);
        $this->assertStringNotContainsString($year, $actual);
    }

    public function formatDayDataProvider()
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

    /**
     * @param string $date
     * @param string $timeZone
     * @return \DateTime
     */
    protected function createDateTime($date, $timeZone)
    {
        return new \DateTime($date, new \DateTimeZone($timeZone));
    }

    /**
     * @dataProvider getDatePatternDataProvider
     */
    public function testGetDatePattern(
        $expectedDateType,
        $expectedTimeType,
        $dateType,
        $timeType,
        $locale
    ) {
        $expected = $this->getPattern($locale, $expectedDateType, $expectedTimeType);
        $this->assertEquals($expected, $this->formatter->getPattern($dateType, $timeType, $locale));
    }

    public function getDatePatternDataProvider()
    {
        return array(
            array(
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'en_US'
            ),
            array(
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'fr_FR'
            ),
            array(
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                'full',
                'full',
                'fr_FR'
            ),
        );
    }

    /**
     * @dataProvider getDateTimeNotModifiedDataProvider
     */
    public function testGetDateTimeReturnsNotModified($date)
    {
        $this->assertSame($date, $this->formatter->getDateTime($date));
    }

    /**
     * @return array
     */
    public function getDateTimeNotModifiedDataProvider()
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

    /**
     * @param string $lang
     * @param string $timeZone
     * @param string $pattern
     *
     * @return \IntlDateFormatter
     */
    protected function getFormatter($lang, $timeZone, $pattern)
    {
        return new \IntlDateFormatter(
            $lang,
            null,
            null,
            $timeZone,
            \IntlDateFormatter::GREGORIAN,
            $pattern
        );
    }

    /**
     * @param string $locale
     * @param int $dateType
     * @param int $timeType
     *
     * @return string
     */
    protected function getPattern($locale, $dateType, $timeType)
    {
        $localeFormatter = new \IntlDateFormatter($locale, $dateType, $timeType, null, \IntlDateFormatter::GREGORIAN);

        // replace non-breaking spaces with spaces
        return str_replace("\xE2\x80\xAF", ' ', $localeFormatter->getPattern());
    }
}
