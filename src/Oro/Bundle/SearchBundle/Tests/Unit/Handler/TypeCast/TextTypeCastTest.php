<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Handler\TypeCast;

use Oro\Bundle\SearchBundle\Exception\TypeCastingException;
use Oro\Bundle\SearchBundle\Handler\TypeCast\IntegerTypeCast;
use Oro\Bundle\SearchBundle\Handler\TypeCast\TextTypeCast;
use Oro\Bundle\SearchBundle\Query\Query;

class TextTypeCastTest extends \PHPUnit\Framework\TestCase
{
    /** @var IntegerTypeCast */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new TextTypeCast();
    }

    public function testCastValue(): void
    {
        $this->assertEquals('string', $this->handler->castValue('string'));
        $this->assertIsString($this->handler->castValue('string'));
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
                'value'=> 1.1
            ]
        ];
    }

    public function testGetType(): void
    {
        $this->assertEquals(Query::TYPE_TEXT, TextTypeCast::getType());
    }
}
