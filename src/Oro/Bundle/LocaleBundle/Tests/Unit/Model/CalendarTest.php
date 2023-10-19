<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Model\Calendar;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class CalendarTest extends TestCase
{
    /**
     * @var Calendar
     */
    protected $calendar;

    /**
     * @var string
     */
    protected $defaultLocale;

    protected function setUp(): void
    {
        IntlTestHelper::requireIntl($this);

        $this->calendar = new Calendar();
        $this->defaultLocale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        if ($this->defaultLocale) {
            \Locale::setDefault($this->defaultLocale);
        }
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

    public function getFirstDayOfWeekDataProvider(): array
    {
        return [
            'en_US, Sunday, Default locale' => [null, Calendar::DOW_SUNDAY, 'en_US'],
            'en_US, Sunday' => ['en_US', Calendar::DOW_SUNDAY],
            'fr_CA, Sunday' => ['fr_CA', Calendar::DOW_SUNDAY],
            'he_IL, Sunday' => ['he_IL', Calendar::DOW_SUNDAY],
            'ar_SA, Sunday' => ['ar_SA', Calendar::DOW_SUNDAY],
            'ko_KR, Sunday' => ['ko_KR', Calendar::DOW_SUNDAY],
            'lo_LA, Sunday' => ['lo_LA', Calendar::DOW_SUNDAY],
            'ja_JP, Sunday' => ['ja_JP', Calendar::DOW_SUNDAY],
            'hi_IN, Sunday' => ['hi_IN', Calendar::DOW_SUNDAY],
            'kn_IN, Sunday' => ['kn_IN', Calendar::DOW_SUNDAY],
            // 'zh_CN, Sunday' => ['zh_CN', Calendar::DOW_MONDAY],
            'ru_RU, Monday' => ['ru_RU', Calendar::DOW_MONDAY],
            'en_GB, Monday' => ['en_GB', Calendar::DOW_MONDAY],
            'sq_AL, Monday' => ['sq_AL', Calendar::DOW_MONDAY],
            'bg_BG, Monday' => ['bg_BG', Calendar::DOW_MONDAY],
            'vi_VN, Monday' => ['vi_VN', Calendar::DOW_MONDAY],
            'it_IT, Monday' => ['it_IT', Calendar::DOW_MONDAY],
            'fr_FR, Monday' => ['fr_FR', Calendar::DOW_MONDAY],
            'eu_ES, Monday' => ['eu_ES', Calendar::DOW_MONDAY],
        ];
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
        $this->assertEquals($expected, $this->calendar->getMonthNames($width), '', 0.0, 10, false, true);
    }

    public function getMonthNamesDataProvider(): array
    {
        return [
            'default wide, default locale' => [
                null,
                null,
                [
                    1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July',
                    'August', 'September', 'October', 'November', 'December',
                ],
                'en_US'
            ],
            'wide, en_US' => [
                Calendar::WIDTH_WIDE,
                'en_US',
                [
                    1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July',
                    'August', 'September', 'October', 'November', 'December',
                ]
            ],
            'abbreviated, en_US' => [
                Calendar::WIDTH_ABBREVIATED,
                'en_US',
                [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            ],
            'short, en_US' => [
                Calendar::WIDTH_SHORT,
                'en_US',
                [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            ],
            'narrow, en_US' => [
                Calendar::WIDTH_NARROW,
                'en_US',
                [1 => 'J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D']
            ],
            'wide, it_IT' => [
                Calendar::WIDTH_WIDE,
                'it_IT',
                [
                    1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio',
                    'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre',
                ]
            ],
            'wide, id_ID' => [
                Calendar::WIDTH_WIDE,
                'id_ID',
                [
                    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                    'Agustus', 'September', 'Oktober', 'November', 'Desember',
                ]
            ],
        ];
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

        $widthToPatternMap = [
            Calendar::WIDTH_ABBREVIATED => 'ccc',
            Calendar::WIDTH_SHORT => 'cccccc',
            Calendar::WIDTH_NARROW => 'ccccc',
            Calendar::WIDTH_WIDE => 'cccc'
        ];
        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            'UTC',
            \IntlDateFormatter::GREGORIAN,
            $widthToPatternMap[$width ? : Calendar::WIDTH_WIDE]
        );
        foreach ($actual as $dayNum => $dayName) {
            $checkDate = new \DateTime('2013/09/0' . $dayNum, new \DateTimeZone('UTC'));
            $expected = $formatter->format((int)$checkDate->format('U'));
            $this->assertEquals($expected, $actual[$dayNum], 'Incorrect day for day #' . $dayNum);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDayOfWeekNamesDataProvider(): array
    {
        return [
            'wide, en_US' => [
                Calendar::WIDTH_WIDE,
                'en_US'
            ],
            'default wide, default locale' => [
                null,
                null,
                'en_US',
            ],
            'abbreviated, en_US' => [
                Calendar::WIDTH_ABBREVIATED,
                'en_US',
            ],
            'short, en_US' => [
                Calendar::WIDTH_SHORT,
                'en_US'
            ],
            'narrow, en_US' => [
                Calendar::WIDTH_NARROW,
                'en_US'
            ],
            'fr_FR' => [
                Calendar::WIDTH_WIDE,
                'fr_FR'
            ],
            'ru_RU' => [
                Calendar::WIDTH_WIDE,
                'ru_RU'
            ],
            'abbreviated, ru_RU' => [
                Calendar::WIDTH_ABBREVIATED,
                'ru_RU'
            ],
            'short, ru_RU' => [
                Calendar::WIDTH_SHORT,
                'ru_RU'
            ],
            'narrow, ru_RU' => [
                Calendar::WIDTH_NARROW,
                'ru_RU'
            ],
        ];
    }

    public function testLocale()
    {
        $this->assertEquals(\Locale::getDefault(), $this->calendar->getLocale());
        $this->calendar->setLocale('ru_RU');
        $this->assertEquals('ru_RU', $this->calendar->getLocale());
    }
}
