<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Entity;

use Oro\Bundle\SearchBundle\Entity\IndexDecimal;
use Oro\Bundle\SearchBundle\Entity\Item;
use PHPUnit\Framework\TestCase;

class IndexDecimalTest extends TestCase
{
    private IndexDecimal $index;

    #[\Override]
    protected function setUp(): void
    {
        $this->index = new IndexDecimal();
    }

    public function testField(): void
    {
        $this->assertNull($this->index->getField());
        $this->index->setField('test_decimal_field');
        $this->assertEquals('test_decimal_field', $this->index->getField());
    }

    public function testValue(): void
    {
        $this->assertNull($this->index->getValue());
        $this->index->setValue(55.25);
        $this->assertEquals(55.25, $this->index->getValue());
    }

    public function testGetId(): void
    {
        $this->assertNull($this->index->getId());
    }

    public function testItem(): void
    {
        $this->assertNull($this->index->getItem());
        $item = new Item();
        $this->index->setItem($item);
        $this->assertEquals($item, $this->index->getItem());
    }
}
