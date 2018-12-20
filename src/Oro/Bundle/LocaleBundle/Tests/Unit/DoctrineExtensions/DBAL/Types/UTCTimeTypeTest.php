<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCTimeType;

class UTCTimeTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UTCTimeType
     */
    protected $type;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    protected function setUp()
    {
        // class has private constructor
        $this->type = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCTimeType')
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->setMethods(['getTimeFormatString'])
            ->getMockForAbstractClass();
        $this->platform->expects($this->any())
            ->method('getTimeFormatString')
            ->will($this->returnValue('H:i:s'));
    }

    /**
     * @param \DateTime $source
     * @param string $expected
     * @dataProvider convertToDatabaseValueDataProvider
     */
    public function testConvertToDatabaseValue($source, $expected)
    {
        $this->assertEquals($expected, $this->type->convertToDatabaseValue($source, $this->platform));
    }

    /**
     * @param string $tzId
     * @return bool
     */
    protected function timezoneExhibitsDST($tzId)
    {
        $date = new \DateTime('now', new \DateTimeZone($tzId));

        return $date->format('I');
    }

    /**
     * @return array
     */
    public function convertToDatabaseValueDataProvider()
    {
        return [
            'null' => [
                'source'   => null,
                'expected' => null,
            ],
            'UTC' => [
                'source' => new \DateTime('08:00:00', new \DateTimeZone('UTC')),
                'expected' => '08:00:00',
            ],
            'positive shift' => [
                'source' => new \DateTime('10:00:00', new \DateTimeZone('Asia/Tokyo')), // UTC+9
                'expected' => '01:00:00',
            ],
            'negative shift' => [
                'source' => new \DateTime('10:00:00', new \DateTimeZone('America/Jamaica')), // UTC-5
                'expected' => '15:00:00',
            ],
            'positive shift with DST' => [
                'source' => new \DateTime('08:00:00', new \DateTimeZone('Europe/Athens')),
                'expected' => $this->timezoneExhibitsDST('Europe/Athens') ? '05:00:00' : '06:00:00',
            ],
            'negative shift with DST' => [
                'source' => new \DateTime('08:00:00', new \DateTimeZone('America/Los_Angeles')),
                'expected' => $this->timezoneExhibitsDST('America/Los_Angeles') ? '15:00:00' : '16:00:00',
            ],
        ];
    }

    public function testConvertToPHPValue()
    {
        $source = '08:00:00';
        $expected = \DateTime::createFromFormat('H:i:s|', '08:00:00', new \DateTimeZone('UTC'));

        $this->assertEquals($expected, $this->type->convertToPHPValue($source, $this->platform));
    }

    /**
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testConvertToPHPValueException()
    {
        $this->type->convertToPHPValue('qwerty', $this->platform);
    }
}
