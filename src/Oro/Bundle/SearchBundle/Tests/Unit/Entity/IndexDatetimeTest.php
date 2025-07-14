<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Entity;

use Oro\Bundle\SearchBundle\Entity\IndexDatetime;
use Oro\Bundle\SearchBundle\Entity\Item;
use PHPUnit\Framework\TestCase;

class IndexDatetimeTest extends TestCase
{
    private IndexDatetime $index;

    #[\Override]
    protected function setUp(): void
    {
        $this->index = new IndexDatetime();
    }

    public function testField(): void
    {
        $this->assertNull($this->index->getField());
        $this->index->setField('test_datetime_field');
        $this->assertEquals('test_datetime_field', $this->index->getField());
    }

    public function testValue(): void
    {
        $this->assertNull($this->index->getValue());
        $this->index->setValue(new \Datetime('2012-12-12'));
        $this->assertEquals('2012-12-12', $this->index->getValue()->format('Y-m-d'));
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
