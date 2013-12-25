<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCTimeType;

class UTCDateTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UTCTimeType
     */
    protected $type;

    /**
     * @var
     */
    protected $platform;

    protected function setUp()
    {
        // class has private constructor
        $this->type = $this->getMockBuilder('Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types\UTCDateType')
            ->setMethods(null)
            ->disableOriginalConstructor()
            ->getMock();

        $this->platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->setMethods(array('getDateFormatString'))
            ->getMockForAbstractClass();
        $this->platform->expects($this->any())
            ->method('getDateFormatString')
            ->will($this->returnValue('Y-m-d'));
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
                'source' => new \DateTime('2013-01-01', new \DateTimeZone('UTC')),
                'expected' => '2013-01-01',
            ),
            'positive shift' => array(
                'source' => new \DateTime('2013-01-01', new \DateTimeZone('Europe/Athens')),
                'expected' => '2012-12-31',
            ),
            'negative shift' => array(
                'source' => new \DateTime('2013-01-01', new \DateTimeZone('America/Los_Angeles')),
                'expected' => '2013-01-01',
            ),
        );
    }

    public function testConvertToPHPValue()
    {
        $source = '2013-01-01';
        $expected = \DateTime::createFromFormat('!Y-m-d', '2013-01-01', new \DateTimeZone('UTC'));

        $this->assertEquals($expected, $this->type->convertToPHPValue($source, $this->platform));
    }
}
