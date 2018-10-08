<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Twig\DateTimeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DateTimeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var DateTimeExtension */
    protected $extension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $formatter;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder(DateTimeFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.date_time', $this->formatter)
            ->getContainer($this);

        $this->extension = new DateTimeExtension($container);
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

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_datetime', [$value, $options])
        );
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

        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_date', [$value, $options])
        );
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

        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_day', [$value, $options])
        );
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

        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_time', [$value, $options])
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_datetime', $this->extension->getName());
    }
}
