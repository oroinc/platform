<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityBundle\DBAL\Types\ConfigObjectType;
use Oro\Component\Config\Common\ConfigObject;

class ConfigObjectTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigObjectType */
    private $type;

    /** @var AbstractPlatform */
    private $platform;

    protected function setUp(): void
    {
        if (!Type::hasType(ConfigObjectType::TYPE)) {
            Type::addType(ConfigObjectType::TYPE, ConfigObjectType::class);
        }
        $this->type = Type::getType(ConfigObjectType::TYPE);
        $this->platform = $this->createMock(AbstractPlatform::class);
    }

    /**
     * @dataProvider testConvertToPHPValueDataProvider
     */
    public function testConvertToPHPValue(?bool $inputData, ?bool $expectedResult, bool $exception = false)
    {
        if ($exception) {
            $this->expectException(ConversionException::class);
            $this->expectExceptionMessage(
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
        $this->assertInstanceOf(ConfigObject::class, $result);
        $this->assertSame($testArray, $result->toArray());
    }

    /**
     * @dataProvider testConvertToDatabaseValueDataProvider
     */
    public function testConvertToDatabaseValue(mixed $inputData, ?string $expectedResult)
    {
        $this->assertSame(
            $expectedResult,
            $this->type->convertToDatabaseValue($inputData, $this->platform)
        );
    }

    public function testConvertToPHPValueDataProvider(): array
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

    public function testConvertToDatabaseValueDataProvider(): array
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
