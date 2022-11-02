<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\DateTimeTypeCast;
use Oro\Bundle\SearchBundle\Query\Query;

class DateTimeTypeCastTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeTypeCast */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new DateTimeTypeCast();
    }

    public function testCastValue(): void
    {
        $value = new \DateTime('now');
        $this->assertEquals($value, $this->handler->castValue($value));
        $this->assertInstanceOf(\DateTime::class, $value);
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
                'value'=> 1.1
            ]
        ];
    }

    public function testGetType(): void
    {
        $this->assertEquals(Query::TYPE_DATETIME, DateTimeTypeCast::getType());
    }
}
