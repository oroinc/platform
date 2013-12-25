<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCTimeType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class UTCDateTimeTypeTest extends \PHPUnit_Framework_TestCase
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
        $this->type = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCDateTimeType')
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->setMethods(array('getDateTimeFormatString'))
            ->getMockForAbstractClass();
        $this->platform->expects($this->any())
            ->method('getDateTimeFormatString')
            ->will($this->returnValue('Y-m-d H:i:s'));
    }

    protected function tearDown()
    {
        unset($this->type);
        unset($this->platform);
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
     * @return array
     */
    public function convertToDatabaseValueDataProvider()
    {
        return array(
            'null' => array(
                'source'   => null,
                'expected' => null,
            ),
            'UTC' => array(
                'source' => new \DateTime('2013-01-01 00:00:00', new \DateTimeZone('UTC')),
                'expected' => '2013-01-01 00:00:00',
            ),
            'positive shift' => array(
                'source' => new \DateTime('2013-01-01 00:00:00', new \DateTimeZone('Europe/Athens')),
                'expected' => '2012-12-31 22:00:00',
            ),
            'negative shift' => array(
                'source' => new \DateTime('2013-01-01 00:00:00', new \DateTimeZone('America/Los_Angeles')),
                'expected' => '2013-01-01 08:00:00',
            ),
        );
    }

    public function testConvertToPHPValue()
    {
        $source = '2013-01-01 08:00:00';
        $expected = new \DateTime('2013-01-01 08:00:00', new \DateTimeZone('UTC'));

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
