<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Model\Calendar;

class CalendarTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Calendar
     */
    protected $calendar;

    /**
     * @var string
     */
    protected $defaultLocale;

    protected function setUp()
    {
        $this->calendar = new Calendar();
        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->defaultLocale);
    }

    /**
     * @dataProvider getFirstDayOfWeekDataProvider
     */
    public function testGetFirstDayOfWeek($locale, $expected, $defaultLocale = null)
    {
        $this->calendar->setLocale($locale);
        if (null !== $defaultLocale) {
            \Locale::setDefault($defaultLocale);
        }
        $this->assertEquals($expected, $this->calendar->getFirstDayOfWeek($locale));
    }

    public function getFirstDayOfWeekDataProvider()
    {
        return array(
            'en_US, Sunday, Default locale' => array(null, Calendar::DOW_SUNDAY, 'en_US'),
            'en_US, Sunday' => array('en_US', Calendar::DOW_SUNDAY),
            'fr_CA, Sunday' => array('fr_CA', Calendar::DOW_SUNDAY),
            'he_IL, Sunday' => array('he_IL', Calendar::DOW_SUNDAY),
            'ar_SA, Sunday' => array('ar_SA', Calendar::DOW_SUNDAY),
            'ko_KR, Sunday' => array('ko_KR', Calendar::DOW_SUNDAY),
            'lo_LA, Sunday' => array('lo_LA', Calendar::DOW_SUNDAY),
            'ja_JP, Sunday' => array('ja_JP', Calendar::DOW_SUNDAY),
            'hi_IN, Sunday' => array('hi_IN', Calendar::DOW_SUNDAY),
            'kn_IN, Sunday' => array('kn_IN', Calendar::DOW_SUNDAY),
            'zh_CN, Sunday' => array('zh_CN', Calendar::DOW_SUNDAY),
            'ru_RU, Monday' => array('ru_RU', Calendar::DOW_MONDAY),
            'en_GB, Monday' => array('en_GB', Calendar::DOW_MONDAY),
            'sq_AL, Monday' => array('sq_AL', Calendar::DOW_MONDAY),
            'bg_BG, Monday' => array('bg_BG', Calendar::DOW_MONDAY),
            'vi_VN, Monday' => array('vi_VN', Calendar::DOW_MONDAY),
            'it_IT, Monday' => array('it_IT', Calendar::DOW_MONDAY),
            'fr_FR, Monday' => array('fr_FR', Calendar::DOW_MONDAY),
            'eu_ES, Monday' => array('eu_ES', Calendar::DOW_MONDAY),
        );
    }

    /**
     * @dataProvider getMonthNamesDataProvider
     */
    public function testGetMonthNames($width, $locale, array $expected, $defaultLocale = null)
    {
        $this->calendar->setLocale($locale);
        if (null !== $defaultLocale) {
            \Locale::setDefault($defaultLocale);
        }
        $this->assertEquals($expected, $this->calendar->getMonthNames($width));
    }

    public function getMonthNamesDataProvider()
    {
        return array(
            'default wide, default locale' => array(
                null,
                null,
                array(
                    1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July',
                    'August', 'September', 'October', 'November', 'December',
                ),
                'en_US'
            ),
            'wide, en_US' => array(
                Calendar::WIDTH_WIDE,
                'en_US',
                array(
                    1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July',
                    'August', 'September', 'October', 'November', 'December',
                )
            ),
            'abbreviated, en_US' => array(
                Calendar::WIDTH_ABBREVIATED,
                'en_US',
                array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec')
            ),
            'short, en_US' => array(
                Calendar::WIDTH_SHORT,
                'en_US',
                array(1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec')
            ),
            'narrow, en_US' => array(
                Calendar::WIDTH_NARROW,
                'en_US',
                array(1 => 'J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D')
            ),
            'wide, it_IT' => array(
                Calendar::WIDTH_WIDE,
                'it_IT',
                array(
                    1 => 'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio',
                    'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre',
                )
            ),
            'wide, id_ID' => array(
                Calendar::WIDTH_WIDE,
                'id_ID',
                array(
                    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                    'Agustus', 'September', 'Oktober', 'November', 'Desember',
                )
            ),
        );
    }

    /**
     * @dataProvider getDayOfWeekNamesDataProvider
     */
    public function testGetDayOfWeekNames($width, $locale, $defaultLocale = null)
    {
        $this->calendar->setLocale($locale);
        if (null !== $defaultLocale) {
            \Locale::setDefault($defaultLocale);
        }
        $actual = $this->calendar->getDayOfWeekNames($width);
        $this->assertCount(7, $actual);

        $widthToPatternMap = array(
            Calendar::WIDTH_ABBREVIATED => 'ccc',
            Calendar::WIDTH_SHORT => 'cccccc',
            Calendar::WIDTH_NARROW => 'ccccc',
            Calendar::WIDTH_WIDE => 'cccc'
        );
        $formatter = new \IntlDateFormatter(
            $locale,
            null,
            null,
            'UTC',
            \IntlDateFormatter::GREGORIAN,
            $widthToPatternMap[$width ? : Calendar::WIDTH_WIDE]
        );
        foreach ($actual as $dayNum => $dayName) {
            $checkDate = new \DateTime('2013/07/0' . $dayNum);
            $expected = $formatter->format((int)$checkDate->format('U'));
            $this->assertEquals($expected, $actual[$dayNum]);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDayOfWeekNamesDataProvider()
    {
        return array(
            'wide, en_US' => array(
                Calendar::WIDTH_WIDE,
                'en_US'
            ),
            'default wide, default locale' => array(
                null,
                null,
                'en_US',
            ),
            'abbreviated, en_US' => array(
                Calendar::WIDTH_ABBREVIATED,
                'en_US',
            ),
            'short, en_US' => array(
                Calendar::WIDTH_SHORT,
                'en_US'
            ),
            'narrow, en_US' => array(
                Calendar::WIDTH_NARROW,
                'en_US'
            ),
            'fr_FR' => array(
                Calendar::WIDTH_WIDE,
                'fr_FR'
            ),
            'ru_RU' => array(
                Calendar::WIDTH_WIDE,
                'ru_RU'
            ),
            'abbreviated, ru_RU' => array(
                Calendar::WIDTH_ABBREVIATED,
                'ru_RU'
            ),
            'short, ru_RU' => array(
                Calendar::WIDTH_SHORT,
                'ru_RU'
            ),
            'narrow, ru_RU' => array(
                Calendar::WIDTH_NARROW,
                'ru_RU'
            ),
        );
    }

    public function testLocale()
    {
        $this->assertEquals(\Locale::getDefault(), $this->calendar->getLocale());
        $this->calendar->setLocale('ru_RU');
        $this->assertEquals('ru_RU', $this->calendar->getLocale());
    }
}
