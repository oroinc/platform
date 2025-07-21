<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Entity;

use Oro\Bundle\SearchBundle\Entity\IndexInteger;
use Oro\Bundle\SearchBundle\Entity\Item;
use PHPUnit\Framework\TestCase;

class IndexIntegerTest extends TestCase
{
    private IndexInteger $index;

    #[\Override]
    protected function setUp(): void
    {
        $this->index = new IndexInteger();
    }

    public function testField(): void
    {
        $this->assertNull($this->index->getField());
        $this->index->setField('test_integer_field');
        $this->assertEquals('test_integer_field', $this->index->getField());
    }

    public function testValue(): void
    {
        $this->assertNull($this->index->getValue());
        $this->index->setValue(100);
        $this->assertEquals(100, $this->index->getValue());
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
