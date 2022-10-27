<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\DecimalTypeCast;
use Oro\Bundle\SearchBundle\Query\Query;

class DecimalTypeCastTest extends \PHPUnit\Framework\TestCase
{
    /** @var DecimalTypeCast */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new DecimalTypeCast();
    }

    /**
     * @param int|float $value
     *
     * @dataProvider validTypesDataProvider
     */
    public function testCastValue($value): void
    {
        $this->assertEquals($value, $this->handler->castValue($value));
        $this->assertIsFloat($this->handler->castValue($value));
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
        ];
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
        ];
    }

    public function testGetType(): void
    {
        $this->assertEquals(Query::TYPE_DECIMAL, DecimalTypeCast::getType());
    }
}
