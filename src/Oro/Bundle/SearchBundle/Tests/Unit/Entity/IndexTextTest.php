<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Entity;

use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\Entity\Item;
use PHPUnit\Framework\TestCase;

class IndexTextTest extends TestCase
{
    private IndexText $index;

    #[\Override]
    protected function setUp(): void
    {
        $this->index = new IndexText();
    }

    public function testField(): void
    {
        $this->assertNull($this->index->getField());
        $this->index->setField('test_text_field');
        $this->assertEquals('test_text_field', $this->index->getField());
    }

    public function testValue(): void
    {
        $this->assertNull($this->index->getValue());
        $this->index->setValue('test_text_value');
        $this->assertEquals('test_text_value', $this->index->getValue());
    }

    public function testValueWithHyphen(): void
    {
        $this->index->setValue('text-value');
        $this->assertEquals('text-value', $this->index->getValue());
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
