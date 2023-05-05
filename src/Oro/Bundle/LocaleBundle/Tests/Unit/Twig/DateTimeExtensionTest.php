<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Twig\DateTimeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DateTimeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var DateTimeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);

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

        $this->formatter->expects($this->once())
            ->method('format')
            ->with($value, $dateType, $timeType, $locale, $timeZone)
            ->willReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            self::callTwigFilter($this->extension, 'oro_format_datetime', [$value, $options])
        );
    }

    public function formatDateDataProvider(): array
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
     * @dataProvider formatDateDataProvider
     */
    public function testFormatDate(
        \DateTime $value,
        string $expected,
        ?string $dateType = null,
        ?string $locale = null,
        ?string $timeZone = null
    ) {
        $options = [
            'dateType' => $dateType,
            'locale' => $locale,
            'timeZone' => $timeZone
        ];

        $this->formatter->expects($this->once())
            ->method('formatDate')
            ->with($value, $dateType, $locale, $timeZone ?: 'UTC')
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_date', [$value, $options])
        );
    }

    public function formatDayDataProvider(): array
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
     * @dataProvider formatDayDataProvider
     */
    public function testFormatDay(\DateTime $value, string $expected, ?string $locale = null)
    {
        $timeZone = null;
        $dateType = null;
        $options = ['locale' => $locale];

        $this->formatter->expects($this->once())
            ->method('formatDay')
            ->with($value, $dateType, $locale, $timeZone ?: 'UTC')
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_day', [$value, $options])
        );
    }

    public function formatTimeDataProvider(): array
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
     * @dataProvider formatTimeDataProvider
     */
    public function testFormatTime(
        \DateTime $value,
        string $expected,
        ?string $timeType = null,
        ?string $locale = null,
        ?string $timeZone = null
    ) {
        $options = [
            'timeType' => $timeType,
            'locale' => $locale,
            'timeZone' => $timeZone
        ];

        $this->formatter->expects($this->once())
            ->method('formatTime')
            ->with($value, $timeType, $locale, $timeZone ?: 'UTC')
            ->willReturn($expected);

        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_time', [$value, $options])
        );
    }
}
