<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Result;

use Oro\Bundle\SearchBundle\Query\Result\Item;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    private Item $item;

    #[\Override]
    protected function setUp(): void
    {
        $this->item = new Item(
            'OroTestBundle:test',
            1,
            'http://example.com',
            [],
            [
                'alias'  => 'test_product',
                'label'  => 'test product',
                'fields' => [
                    [
                        'name'        => 'name',
                        'target_type' => 'text',
                    ],
                ],
            ]
        );
    }

    public function testGetEntityName(): void
    {
        $this->assertEquals('OroTestBundle:test', $this->item->getEntityName());
    }

    public function testGetRecordId(): void
    {
        $this->assertEquals(1, $this->item->getRecordId());
    }

    public function testToArray(): void
    {
        $result = $this->item->toArray();
        $this->assertEquals('OroTestBundle:test', $result['entity_name']);
        $this->assertEquals(1, $result['record_id']);
    }

    public function testRecordUrl(): void
    {
        $this->item->setRecordUrl('http://example.com');
        $this->assertEquals('http://example.com', $this->item->getRecordUrl());
    }

    public function testSelectedData(): void
    {
        $this->item->setSelectedData(['sku' => 'abc123']);
        $this->assertEquals(['sku' => 'abc123'], $this->item->getSelectedData());
    }

    public function testGetEntityConfig(): void
    {
        $result = $this->item->getEntityConfig();
        $this->assertEquals('test_product', $result['alias']);
    }
}
