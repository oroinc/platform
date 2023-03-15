<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Oro\Bundle\EntityBundle\DoctrineExtensions\DBAL\Types\DurationType;

class DurationTypeTest extends \PHPUnit\Framework\TestCase
{
    private DurationType $type;

    protected function setUp(): void
    {
        $this->type = new DurationType();
    }

    /**
     * @dataProvider convertToDatabaseValueDataProvider
     */
    public function testConvertToDatabaseValue(mixed $value, mixed $expected)
    {
        $this->assertSame(
            $expected,
            $this->type->convertToDatabaseValue($value, $this->createMock(AbstractPlatform::class))
        );
    }

    public function convertToDatabaseValueDataProvider(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['', ''],
            'string' => ['10', '10'],
            'integer' => [10, 10],
        ];
    }

    /**
     * @dataProvider convertToPHPValueDataProvider
     */
    public function testConvertToPHPValue(mixed $value, mixed $expected)
    {
        $this->assertSame(
            $expected,
            $this->type->convertToPHPValue($value, $this->createMock(AbstractPlatform::class))
        );
    }

    public function convertToPHPValueDataProvider(): array
    {
        return [
            'null' => [null, null],
            'string' => ['10', 10],
            'integer' => [10, 10],
        ];
    }
}
