<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

use Oro\Component\Config\Common\ConfigObject;
use Oro\Bundle\EntityBundle\DBAL\Types\ConfigObject as ConfigType;

class ConfigObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigType
     */
    protected $type;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    protected function setUp()
    {
        if (!Type::hasType(ConfigType::TYPE)) {
            Type::addType(ConfigType::TYPE, 'Oro\Bundle\EntityBundle\DBAL\Types\ConfigObject');
        }
        $this->type = Type::getType(ConfigType::TYPE);
        $this->platform = $this->getMockBuilder('Doctrine\DBAL\Platforms\AbstractPlatform')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider testConvertToPHPValueDataProvider
     *
     * @param mixed $inputData
     * @param null | string
     * @param bool $exception
     */
    public function testConvertToPHPValue($inputData, $expectedResult, $exception = false)
    {
        if ($exception) {
            $this->setExpectedException(
                'Doctrine\DBAL\Types\ConversionException',
                'Could not convert database value "' . $inputData . '" to Doctrine Type config_object'
            );
        }

        $this->assertSame(
            $expectedResult,
            $this->type->convertToPHPValue($inputData, $this->platform)
        );
    }

    public function testGetConfigObjectFromConvertToPHPValueMethod()
    {
        $testArray = ['name' => 'test'];
        $result = $this->type->convertToPHPValue(json_encode($testArray), $this->platform);
        $this->assertInstanceOf('Oro\Component\Config\Common\ConfigObject', $result);
        $this->assertSame($testArray, $result->toArray());
    }

    /**
     * @dataProvider testConvertToDatabaseValueDataProvider
     *
     * @param mixed $inputData
     * @param null | string
     */
    public function testConvertToDatabaseValue($inputData, $expectedResult)
    {
        $this->assertSame(
            $expectedResult,
            $this->type->convertToDatabaseValue($inputData, $this->platform)
        );
    }

    public function testConvertToPHPValueDataProvider()
    {
        return [
            'null input' => [
                'input' => null,
                'expected' => null
            ],
            'incorrect input' => [
                'input' => false,
                'expected' => false,
                'exception' => true
            ]
        ];
    }

    public function testConvertToDatabaseValueDataProvider()
    {
        return [
            'null input' => [
                'input' => null,
                'expected' => null
            ],
            'object input' => [
                'input' => ConfigObject::create(['name' => 'test']),
                'expected' => json_encode(['name' => 'test'])
            ],
            'incorrect input' => [
                'input' => 'some incorrect value',
                'expected' => json_encode([])
            ]
        ];
    }
}
