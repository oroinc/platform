<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCTimeType;

class UTCTimeTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UTCTimeType|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $type;

    /**
     * @var AbstractPlatform|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $platform;

    protected function setUp(): void
    {
        // class has private constructor
        $this->type = $this->getMockBuilder(UTCTimeType::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTimeFormatString'])
            ->getMockForAbstractClass();
        $this->platform->expects($this->any())
            ->method('getTimeFormatString')
            ->will($this->returnValue('H:i:s'));
    }

    /**
     * @param string $sourceTime
     * @param string $sourceTimeZone
     * @param string $expected
     *
     * @dataProvider convertToDatabaseValueWhenNoDstDataProvider
     */
    public function testConvertToDatabaseValueWhenNoDst(?string $sourceTime, ?string $sourceTimeZone, ?string $expected)
    {
        $source = new \DateTime('01 Jan 2019 ' . $sourceTime, new \DateTimeZone($sourceTimeZone));
        $this->assertEquals($expected, $this->type->convertToDatabaseValue($source, $this->platform));
    }

    /**
     * @return array
     */
    public function convertToDatabaseValueWhenNoDstDataProvider()
    {
        return [
            'UTC' => [
                'sourceTime' => '08:00:00',
                'sourceTimeZone' => 'UTC',
                'expected' => '08:00:00',
            ],
            'positive shift' => [
                'sourceTime' => '10:00:00',
                'sourceTimeZone' => 'Asia/Tokyo', // UTC+9
                'expected' => '01:00:00',
            ],
            'negative shift' => [
                'sourceTime' => '10:00:00',
                'sourceTimeZone' => 'America/Jamaica', // UTC-5
                'expected' => '15:00:00',
            ],
            'positive shift with DST' => [
                'sourceTime' => '08:00:00',
                'sourceTimeZone' => 'Europe/Athens',
                'expected' => '06:00:00',
            ],
            'negative shift with DST' => [
                'sourceTime' => '08:00:00',
                'sourceTimeZone' => 'America/Los_Angeles',
                'expected' => '16:00:00',
            ],
        ];
    }

    /**
     * @param string $sourceTime
     * @param string $sourceTimeZone
     * @param string $expected
     *
     * @dataProvider convertToDatabaseValueWhenDstDataProvider
     */
    public function testConvertToDatabaseValueWhenDst(?string $sourceTime, ?string $sourceTimeZone, ?string $expected)
    {
        $source = new \DateTime('01 Jun 2019 ' . $sourceTime, new \DateTimeZone($sourceTimeZone));
        $this->assertEquals($expected, $this->type->convertToDatabaseValue($source, $this->platform));
    }

    /**
     * @return array
     */
    public function convertToDatabaseValueWhenDstDataProvider()
    {
        return [
            'positive shift with DST' => [
                'sourceTime' => '08:00:00',
                'sourceTimeZone' => 'Europe/Athens',
                'expected' => '05:00:00',
            ],
            'negative shift with DST' => [
                'sourceTime' => '08:00:00',
                'sourceTimeZone' => 'America/Los_Angeles',
                'expected' => '15:00:00',
            ],
        ];
    }

    public function testConvertToDatabaseValueWhenNull()
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    public function testConvertToPHPValue()
    {
        $source = '08:00:00';
        $expected = \DateTime::createFromFormat('H:i:s|', '08:00:00', new \DateTimeZone('UTC'));

        $this->assertEquals($expected, $this->type->convertToPHPValue($source, $this->platform));
    }

    public function testConvertToPHPValueException()
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);
        $this->type->convertToPHPValue('qwerty', $this->platform);
    }
}
