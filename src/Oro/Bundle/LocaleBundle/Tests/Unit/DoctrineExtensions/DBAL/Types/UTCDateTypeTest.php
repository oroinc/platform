<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCDateType;

class UTCDateTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var UTCDateType */
    private $type;

    /** @var AbstractPlatform|\PHPUnit\Framework\MockObject\MockObject */
    private $platform;

    protected function setUp(): void
    {
        $this->type = new UTCDateType();

        $this->platform = $this->createMock(AbstractPlatform::class);
        $this->platform->expects($this->any())
            ->method('getDateFormatString')
            ->willReturn('Y-m-d');
    }

    /**
     * @dataProvider convertToDatabaseValueWhenNoDstDataProvider
     */
    public function testConvertToDatabaseValueWhenNoDst(string $sourceDate, string $sourceTimeZone, string $expected)
    {
        $source = new \DateTime($sourceDate . '02:00:00', new \DateTimeZone($sourceTimeZone));

        $this->assertEquals($expected, $this->type->convertToDatabaseValue($source, $this->platform));
    }

    public function convertToDatabaseValueWhenNoDstDataProvider(): array
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

    public function convertToDatabaseValueWhenDstDataProvider(): array
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
        $this->expectException(ConversionException::class);
        $this->type->convertToPHPValue('qwerty', $this->platform);
    }
}
