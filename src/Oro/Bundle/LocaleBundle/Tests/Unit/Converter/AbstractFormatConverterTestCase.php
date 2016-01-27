<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;

abstract class AbstractFormatConverterTestCase extends \PHPUnit_Framework_TestCase
{
    const LOCALE_EN = 'en';
    const LOCALE_RU = 'ru';

    /**
     * @var DateTimeFormatConverterInterface
     */
    protected $converter;

    /**
     * @var LocaleSettings
     */
    protected $formatter;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var array
     */
    protected $localFormatMap = [
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
    ];

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->setMethods(['getPattern'])
            ->getMock();

        $this->formatter->expects($this->any())
            ->method('getPattern')
            ->will($this->returnValueMap($this->localFormatMap));

        $this->translator = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator->method('trans')
            ->will(
                $this->returnCallback(
                    function ($one, $two, $tree, $locale) {
                        if ($locale == self::LOCALE_EN) {
                            return 'MMM d';
                        }

                        if ($locale == self::LOCALE_RU) {
                            return 'd.MMM';
                        }

                        return '';
                    }
                )
            );

        $this->converter = $this->createFormatConverter();
    }

    protected function tearDown()
    {
        unset($this->formatter);
        unset($this->converter);
    }

    /**
     * @return DateTimeFormatConverterInterface
     */
    abstract protected function createFormatConverter();

    /**
     * @param string $expected
     * @param int    $dateFormat
     * @param string $locale
     *
     * @dataProvider getDateFormatDataProvider
     */
    public function testGetDateFormat($expected, $dateFormat, $locale)
    {
        $this->assertEquals($expected, $this->converter->getDateFormat($dateFormat, $locale));
    }

    /**
     * @return array
     */
    abstract public function getDateFormatDataProvider();

    /**
     * @param string $expected
     * @param int    $timeFormat
     * @param string $locale
     *
     * @dataProvider getTimeFormatDataProvider
     */
    public function testGetTimeFormat($expected, $timeFormat, $locale)
    {
        $this->assertEquals($expected, $this->converter->getTimeFormat($timeFormat, $locale));
    }

    /**
     * @return array
     */
    abstract public function getTimeFormatDataProvider();

    /**
     * @param string $expected
     * @param int    $dateFormat
     * @param int    $timeFormat
     * @param string $locale
     *
     * @dataProvider getDateTimeFormatDataProvider
     */
    public function testGetDateTimeFormat($expected, $dateFormat, $timeFormat, $locale)
    {
        $this->assertEquals($expected, $this->converter->getDateTimeFormat($dateFormat, $timeFormat, $locale));
    }

    /**
     * @return array
     */
    abstract public function getDateTimeFormatDataProvider();

    /**
     * @param string $expected
     * @param string $locale
     *
     * @dataProvider getDateFormatDayProvider
     */
    public function testGetDayFormat($expected, $locale)
    {
        $this->assertEquals($expected, $this->converter->getDayFormat($locale));
    }

    /**
     * @return array
     */
    abstract public function getDateFormatDayProvider();
}
