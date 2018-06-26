<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterRegistry;
use Oro\Bundle\LocaleBundle\Twig\DateFormatExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DateFormatExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    const TEST_TYPE = 'test_format_type';
    const TEST_FORMAT = 'MMM, d y t';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $converterRegistry;

    /** @var DateFormatExtension */
    protected $extension;

    protected function setUp()
    {
        $this->converterRegistry =$this->getMockBuilder(DateTimeFormatConverterRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_locale.format_converter.date_time.registry', $this->converterRegistry)
            ->getContainer($this);

        $this->extension = new DateFormatExtension($container);
    }

    protected function tearDown()
    {
        unset($this->converterRegistry);
        unset($this->extension);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_dateformat', $this->extension->getName());
    }

    public function testGetDateFormat()
    {
        $locale = 'en';
        $dateType = 'short';

        $formatConverter = $this->createFormatConverter();
        $formatConverter->expects($this->once())
            ->method('getDateFormat')
            ->with($dateType, $locale)
            ->will($this->returnValue(self::TEST_FORMAT));

        $this->converterRegistry->expects($this->once())
            ->method('getFormatConverter')
            ->with(self::TEST_TYPE)
            ->will($this->returnValue($formatConverter));

        $this->assertEquals(
            self::TEST_FORMAT,
            self::callTwigFunction($this->extension, 'oro_date_format', [self::TEST_TYPE, $dateType, $locale])
        );
    }

    public function testGetTimeFormat()
    {
        $locale = 'en';
        $timeType = 'short';

        $formatConverter = $this->createFormatConverter();
        $formatConverter->expects($this->once())
            ->method('getTimeFormat')
            ->with($timeType, $locale)
            ->will($this->returnValue(self::TEST_FORMAT));

        $this->converterRegistry->expects($this->once())
            ->method('getFormatConverter')
            ->with(self::TEST_TYPE)
            ->will($this->returnValue($formatConverter));

        $this->assertEquals(
            self::TEST_FORMAT,
            self::callTwigFunction($this->extension, 'oro_time_format', [self::TEST_TYPE, $timeType, $locale])
        );
    }

    public function testGetDateTimeFormat()
    {
        $locale = 'en';
        $dateType = 'medium';
        $timeType = 'short';

        $formatConverter = $this->createFormatConverter();
        $formatConverter->expects($this->once())
            ->method('getDateTimeFormat')
            ->with($dateType, $timeType, $locale)
            ->will($this->returnValue(self::TEST_FORMAT));

        $this->converterRegistry->expects($this->once())
            ->method('getFormatConverter')
            ->with(self::TEST_TYPE)
            ->will($this->returnValue($formatConverter));

        $this->assertEquals(
            self::TEST_FORMAT,
            self::callTwigFunction(
                $this->extension,
                'oro_datetime_format',
                [self::TEST_TYPE, $dateType, $timeType, $locale]
            )
        );
    }

    public function testGetDateTimeFormatterList()
    {
        $formatConverters = array(
            'first'  => $this->createFormatConverter(),
            'second' => $this->createFormatConverter(),
        );
        $this->converterRegistry->expects($this->once())
            ->method('getFormatConverters')
            ->will($this->returnValue($formatConverters));

        $this->assertEquals(
            array_keys($formatConverters),
            self::callTwigFunction($this->extension, 'oro_datetime_formatter_list', [])
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFormatConverter()
    {
        return $this->getMockBuilder('Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }
}
