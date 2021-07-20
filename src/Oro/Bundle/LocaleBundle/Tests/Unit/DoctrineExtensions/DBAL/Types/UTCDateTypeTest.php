<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCDateType;

class UTCDateTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UTCDateType|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $type;

    /**
     * @var AbstractPlatform|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $platform;

    protected function setUp(): void
    {
        // class has private constructor
        $this->type = $this->getMockBuilder(UTCDateType::class)
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platform = $this->getMockBuilder(AbstractPlatform::class)
            ->disableOriginalConstructor()
            ->setMethods(array('getDateFormatString'))
            ->getMockForAbstractClass();
        $this->platform->expects($this->any())
            ->method('getDateFormatString')
            ->will($this->returnValue('Y-m-d'));
    }

    /**
     * @dataProvider convertToDatabaseValueWhenNoDstDataProvider
     */
    public function testConvertToDatabaseValueWhenNoDst(string $sourceDate, string $sourceTimeZone, string $expected)
    {
        $source = new \DateTime($sourceDate . '02:00:00', new \DateTimeZone($sourceTimeZone));

        $this->assertEquals($expected, $this->type->convertToDatabaseValue($source, $this->platform));
    }

    /**
     * @return array
     */
    public function convertToDatabaseValueWhenNoDstDataProvider()
    {
        return [
            'UTC' => [
                'sourceDate' => '2013-01-01',
                'sourceTimeZone' => 'UTC',
                'expected' => '2013-01-01',
            ],
            'positive shift' => [
                'sourceDate' => '2013-01-01',
                'sourceTimeZone' => 'Europe/Athens',
                'expected' => '2013-01-01',
            ],
            'negative shift' => [
                'sourceDate' => '2013-01-01',
                'sourceTimeZone' => 'America/Los_Angeles',
                'expected' => '2013-01-01',
            ],
        ];
    }

    /**
     * @dataProvider convertToDatabaseValueWhenDstDataProvider
     */
    public function testConvertToDatabaseValueWhenDst(string $sourceDate, string $sourceTimeZone, string $expected)
    {
        $source = new \DateTime($sourceDate . '02:00:00', new \DateTimeZone($sourceTimeZone));

        $this->assertEquals($expected, $this->type->convertToDatabaseValue($source, $this->platform));
    }

    /**
     * @return array
     */
    public function convertToDatabaseValueWhenDstDataProvider()
    {
        return [
            'UTC' => [
                'sourceDate' => '2013-06-01',
                'sourceTimeZone' => 'UTC',
                'expected' => '2013-06-01',
            ],
            'positive shift' => [
                'sourceDate' => '2013-06-01',
                'sourceTimeZone' => 'Europe/Athens',
                'expected' => '2013-05-31',
            ],
            'negative shift' => [
                'sourceDate' => '2013-06-01',
                'sourceTimeZone' => 'America/Los_Angeles',
                'expected' => '2013-06-01',
            ],
        ];
    }

    public function testConvertToDatabaseValueWhenNull()
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    public function testConvertToPHPValue()
    {
        $source = '2013-01-01';
        $expected = \DateTime::createFromFormat('!Y-m-d', '2013-01-01', new \DateTimeZone('UTC'));

        $this->assertEquals($expected, $this->type->convertToPHPValue($source, $this->platform));
    }

    public function testConvertToPHPValueException()
    {
        $this->expectException(\Doctrine\DBAL\Types\ConversionException::class);
        $this->type->convertToPHPValue('qwerty', $this->platform);
    }
}
