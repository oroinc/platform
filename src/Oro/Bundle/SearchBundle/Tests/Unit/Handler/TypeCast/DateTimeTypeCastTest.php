<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\DateTimeTypeCast;
use PHPUnit\Framework\TestCase;

class DateTimeTypeCastTest extends TestCase
{
    private DateTimeTypeCast $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler = new DateTimeTypeCast();
    }

    public function testCastValue(): void
    {
        $value = new \DateTime('now');
        self::assertSame($value, $this->handler->castValue($value));
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
        $this->expectExceptionMessage('The value cannot be cast to the "datetime" type');
        $this->handler->castValue($value);
    }

    public function invalidTypesDataProvider(): array
    {
        return [
            'string' => [
                'value' => 'string'
            ],
            'boolean' => [
                'value' => false
            ],
            'integer' => [
                'value' => 1
            ],
            'decimal' => [
                'value' => 1.1
            ]
        ];
    }
}
