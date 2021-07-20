<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCDateTimeType;

class UTCDateTimeTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UTCDateTimeType|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $type;

    /**
     * @var AbstractPlatform|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $platform;

    protected function setUp(): void
    {
        // class has private constructor
        $this->type = $this->getMockBuilder(UTCDateTimeType::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDateTimeFormatString'))
            ->getMockForAbstractClass();
        $this->platform->expects($this->any())
            ->method('getDateTimeFormatString')
            ->will($this->returnValue('Y-m-d H:i:s'));
    }

    /**
     * @dataProvider convertToDatabaseValueWhenDataProvider
     */
    public function testConvertToDatabaseValue(string $sourceDateTime, string $sourceTimeZone, string $expected)
    {
        $source = new \DateTime($sourceDateTime, new \DateTimeZone($sourceTimeZone));

        $this->assertEquals($expected, $this->type->convertToDatabaseValue($source, $this->platform));
    }

    /**
     * @return array
     */
    public function convertToDatabaseValueWhenDataProvider()
    {
        return [
            'UTC' => [
                'sourceDateTime' => '2013-01-01 00:00:00',
                'sourceTimeZone' => 'UTC',
                'expected' => '2013-01-01 00:00:00',
            ],
            'positive shift when no DST' => [
                'sourceDateTime' => '2013-01-01 00:00:00',
                'sourceTimeZone' => 'Europe/Athens',
                'expected' => '2012-12-31 22:00:00',
            ],
            'negative shift when no DST' => [
                'sourceDateTime' => '2013-01-01 00:00:00',
                'sourceTimeZone' => 'America/Los_Angeles',
                'expected' => '2013-01-01 08:00:00',
            ],
            'positive shift when DST' => [
                'sourceDateTime' => '2013-06-01 00:00:00',
                'sourceTimeZone' => 'Europe/Athens',
                'expected' => '2013-05-31 21:00:00',
            ],
            'negative shift when DST' => [
                'sourceDateTime' => '2013-06-01 00:00:00',
                'sourceTimeZone' => 'America/Los_Angeles',
                'expected' => '2013-06-01 07:00:00',
            ],
        ];
    }

    public function testConvertToDatabaseValueWhenNull()
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    public function testConvertToPHPValue()
    {
        $source = '2013-01-01 08:00:00';
        $expected = new \DateTime('2013-01-01 08:00:00', new \DateTimeZone('UTC'));

        $this->assertEquals($expected, $this->type->convertToPHPValue($source, $this->platform));
    }

    public function testConvertToPHPValueException()
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);
        $this->type->convertToPHPValue('qwerty', $this->platform);
    }
}
