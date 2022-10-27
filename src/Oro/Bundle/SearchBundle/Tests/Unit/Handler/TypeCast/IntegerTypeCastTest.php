<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\IntegerTypeCast;
use Oro\Bundle\SearchBundle\Query\Query;

class IntegerTypeCastTest extends \PHPUnit\Framework\TestCase
{
    /** @var IntegerTypeCast */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new IntegerTypeCast();
    }

    /**
     * @dataProvider validTypesDataProvider
     */
    public function testCastValue($value, $expected): void
    {
        $this->assertEquals($expected, $this->handler->castValue($value));
        $this->assertIsInt($this->handler->castValue($value));
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
            ],
        ];
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
                'value'=> 1.1
            ]
        ];
    }

    public function testGetType(): void
    {
        $this->assertEquals(Query::TYPE_INTEGER, IntegerTypeCast::getType());
    }
}
