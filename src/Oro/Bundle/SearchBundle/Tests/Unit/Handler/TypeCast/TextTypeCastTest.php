<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\TextTypeCast;
use Oro\Bundle\SearchBundle\Tests\Unit\Stub\EntityStub;
use PHPUnit\Framework\TestCase;

class TextTypeCastTest extends TestCase
{
    private TextTypeCast $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler = new TextTypeCast();
    }

    public function testCastValue(): void
    {
        self::assertEquals('string', $this->handler->castValue('string'));
    }

    public function testCastValueForStringableObject(): void
    {
        $value = new EntityStub(1, 'test');
        self::assertEquals('test', $this->handler->castValue($value));
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
        $this->expectExceptionMessage('The value cannot be cast to the "text" type.');
        $this->handler->castValue($value);
    }

    public function invalidTypesDataProvider(): array
    {
        return [
            'datetime' => [
                'value' => new \DateTime('now')
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
