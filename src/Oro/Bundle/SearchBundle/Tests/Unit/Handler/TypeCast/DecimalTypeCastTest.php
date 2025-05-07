<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\DecimalTypeCast;
use PHPUnit\Framework\TestCase;

class DecimalTypeCastTest extends TestCase
{
    private DecimalTypeCast $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler = new DecimalTypeCast();
    }

    /**
     * @dataProvider validTypesDataProvider
     */
    public function testCastValue(mixed $value): void
    {
        self::assertEquals($value, $this->handler->castValue($value));
        self::assertIsFloat($this->handler->castValue($value));
    }

    public function validTypesDataProvider(): array
    {
        return [
            'integer' => [
                'value' => 1,
                'expected' => 1.0
            ],
            'decimal' => [
                'value' => 1.1,
                'expected' => 1.1
            ],
            'numeric' => [
                'value' => '123.123000000',
                'expected' => 123.123
            ]
        ];
    }

    public function testCastValueForObject(): void
    {
        $value = new \stdClass();
        self::assertSame($value, $this->handler->castValue($value));
    }

    /**
     * @dataProvider invalidTypesDataProvider
     */
    public function testCastValueWithUnsupportedValue($value): void
    {
        $this->expectException(TypeCastingException::class);
        $this->expectExceptionMessage('The value cannot be cast to the "decimal" type.');
        $this->handler->castValue($value);
    }

    public function invalidTypesDataProvider(): array
    {
        return [
            'string' => [
                'value' => 'string'
            ],
            'datetime' => [
                'value' => new \DateTime('now')
            ],
            'numeric' => [
                'value' => '123,123000000'
            ],
            'numeric with symbol' => [
                'value' => 'E123.123000000'
            ],
        ];
    }
}
