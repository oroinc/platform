<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Result;

use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;

class ItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Item|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $item;

    /**
     * @var Product
     */
    protected $product;

    protected function setUp()
    {
        $this->product = new Product();
        $this->product->setName('test product');

        $this->item = new Item(
            'OroTestBundle:test',
            1,
            'test title',
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

    public function testGetEntityName()
    {
        $this->assertEquals('OroTestBundle:test', $this->item->getEntityName());
    }

    public function testGetRecordId()
    {
        $this->assertEquals(1, $this->item->getRecordId());
    }



    public function testToArray()
    {
        $result = $this->item->toArray();
        $this->assertEquals('OroTestBundle:test', $result['entity_name']);
        $this->assertEquals(1, $result['record_id']);
        $this->assertEquals('test title', $result['record_string']);
    }

    public function testRecordTitle()
    {
        $this->item->setRecordTitle('test title');
        $this->assertEquals('test title', $this->item->getRecordTitle());
    }

    public function testRecordUrl()
    {
        $this->item->setRecordUrl('http://example.com');
        $this->assertEquals('http://example.com', $this->item->getRecordUrl());
    }

    public function testSelectedData()
    {
        $this->item->setSelectedData(['sku' => 'abc123']);
        $this->assertEquals(['sku' => 'abc123'], $this->item->getSelectedData());
    }

    public function testGetEntityConfig()
    {
        $result = $this->item->getEntityConfig();
        $this->assertEquals('test_product', $result['alias']);
    }
}
