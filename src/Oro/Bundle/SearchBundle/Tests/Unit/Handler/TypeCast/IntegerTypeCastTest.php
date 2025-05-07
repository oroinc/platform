<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\IntegerTypeCast;
use PHPUnit\Framework\TestCase;

class IntegerTypeCastTest extends TestCase
{
    private IntegerTypeCast $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler = new IntegerTypeCast();
    }

    /**
     * @dataProvider validTypesDataProvider
     */
    public function testCastValue($value, $expected): void
    {
        self::assertSame($expected, $this->handler->castValue($value));
    }

    public function validTypesDataProvider(): array
    {
        return [
            'integer' => [
                'value' => 1,
                'expected' => 1
            ],
            'boolean(true)' => [
                'value' => true,
                'expected' => 1
            ],
            'boolean(false)' => [
                'value' => false,
                'expected' => 0
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
        $this->expectExceptionMessage('The value cannot be cast to the "integer" type.');
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
            'decimal' => [
                'value' => 1.1
            ]
        ];
    }
}
