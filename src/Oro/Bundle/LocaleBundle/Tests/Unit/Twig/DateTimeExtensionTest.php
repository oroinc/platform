<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Twig\DateTimeExtension;

class DateTimeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateTimeExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new DateTimeExtension($this->formatter);
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(4, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertEquals('oro_format_datetime', $filters[0]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[1]);
        $this->assertEquals('oro_format_date', $filters[1]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[2]);
        $this->assertEquals('oro_format_day', $filters[2]->getName());

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[3]);
        $this->assertEquals('oro_format_time', $filters[3]->getName());
    }

    public function testFormatDateTime()
    {
        $value = new \DateTime('2013-12-31 00:00:00');
        $dateType = 'short';
        $timeType = 'short';
        $locale = 'en_US';
        $timeZone = 'America/Los_Angeles';
        $options = [
            'dateType' => $dateType,
            'timeType' => $timeType,
            'locale' => $locale,
            'timeZone' => $timeZone
        ];
        $expectedResult = '12/31/13 12:00 AM';

        $this->formatter->expects($this->once())->method('format')
            ->with($value, $dateType, $timeType, $locale, $timeZone)
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->extension->formatDateTime($value, $options));
    }

    /**
     * @return array
     */
    public function formatDateDataProvider()
    {
        return [
            'default options' => [
                'value' => new \DateTime('2013-12-31 00:00:00'),
                'expected' => '12/31/13',
            ],
            'custom options' => [
                'value' => new \DateTime('2013-12-31 00:00:00'),
                'expected' => '12/31/13',
                'dateType' => 'short',
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
            ],
        ];
    }

    /**
     * @param \DateTime $value
     * @param string $dateType
     * @param string $locale
     * @param string $timeZone
     * @param string $expected
     * @dataProvider formatDateDataProvider
     */
    public function testFormatDate($value, $expected, $dateType = null, $locale = null, $timeZone = null)
    {
        $options = [
            'dateType' => $dateType,
            'locale' => $locale,
            'timeZone' => $timeZone
        ];

        $this->formatter->expects($this->once())->method('formatDate')
            ->with($value, $dateType, $locale, $timeZone ?: 'UTC')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->extension->formatDate($value, $options));
    }

    /**
     * @return array
     */
    public function formatDayDataProvider()
    {
        return [
            'default options' => [
                'value' => new \DateTime('2013-12-31 00:00:00'),
                'expected' => 'Dec 31',
            ],
            'custom options' => [
                'value' => new \DateTime('2013-12-31 00:00:00'),
                'expected' => '31 декабря',
                'locale' => 'ru_RU',
            ],
        ];
    }

    /**
     * @param \DateTime $value
     * @param string $expected
     * @param string $locale
     * @dataProvider formatDayDataProvider
     */
    public function testFormatDay($value, $expected, $locale = null)
    {
        $timeZone = null;
        $dateType = null;
        $options = ['locale' => $locale];

        $this->formatter->expects($this->once())->method('formatDay')
            ->with($value, $dateType, $locale, $timeZone ?: 'UTC')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->extension->formatDay($value, $options));
    }

    /**
     * @return array
     */
    public function formatTimeDataProvider()
    {
        return [
            'default options' => [
                'value' => new \DateTime('2013-12-31 00:00:00'),
                'expected' => '12 AM',
            ],
            'custom options' => [
                'value' => new \DateTime('2013-12-31 00:00:00'),
                'expected' => '12 AM',
                'timeType' => 'short',
                'locale' => 'en_US',
                'timeZone' => 'America/Los_Angeles',
            ],
        ];
    }

    /**
     * @param \DateTime $value
     * @param string $timeType
     * @param string $locale
     * @param string $timeZone
     * @param string $expected
     * @dataProvider formatTimeDataProvider
     */
    public function testFormatTime($value, $expected, $timeType = null, $locale = null, $timeZone = null)
    {
        $options = [
            'timeType' => $timeType,
            'locale' => $locale,
            'timeZone' => $timeZone
        ];

        $this->formatter->expects($this->once())->method('formatTime')
            ->with($value, $timeType, $locale, $timeZone ?: 'UTC')
            ->will($this->returnValue($expected));

        $this->assertEquals($expected, $this->extension->formatTime($value, $options));
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_datetime', $this->extension->getName());
    }
}
