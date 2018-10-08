<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Model\Calendar;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Twig\CalendarExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class CalendarExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var CalendarExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $localeSettings;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $calendar;

    protected function setUp()
    {
        $this->localeSettings = $this->getMockBuilder(LocaleSettings::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendar = $this->getMockBuilder(Calendar::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_locale.settings', $this->localeSettings)
            ->getContainer($this);

        $this->extension = new CalendarExtension($container);
    }

    public function testGetMonthNames()
    {
        $width = Calendar::WIDTH_NARROW;
        $locale = 'en_US';
        $expectedResult = array('expected_result');

        $this->calendar->expects($this->once())->method('getMonthNames')
            ->with($width)
            ->will($this->returnValue($expectedResult));

        $this->localeSettings->expects($this->once())->method('getCalendar')
            ->with($locale)
            ->will($this->returnValue($this->calendar));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_calendar_month_names', [$width, $locale])
        );
    }

    public function testGetDayOfWeekNames()
    {
        $width = Calendar::WIDTH_ABBREVIATED;
        $locale = 'en_US';
        $expectedResult = array('expected_result');

        $this->calendar->expects($this->once())->method('getDayOfWeekNames')
            ->with($width)
            ->will($this->returnValue($expectedResult));

        $this->localeSettings->expects($this->once())->method('getCalendar')
            ->with($locale)
            ->will($this->returnValue($this->calendar));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_calendar_day_of_week_names', [$width, $locale])
        );
    }

    public function testGetFirstDayOfWeek()
    {
        $locale = 'en_US';
        $expectedResult = Calendar::DOW_MONDAY;

        $this->calendar->expects($this->once())->method('getFirstDayOfWeek')
            ->with()
            ->will($this->returnValue($expectedResult));

        $this->localeSettings->expects($this->once())->method('getCalendar')
            ->with($locale)
            ->will($this->returnValue($this->calendar));

        $this->assertEquals(
            $expectedResult,
            self::callTwigFunction($this->extension, 'oro_calendar_first_day_of_week', [$locale])
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_calendar', $this->extension->getName());
    }
}
