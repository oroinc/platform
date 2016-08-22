<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query\Result;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;

class ItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $om;

    /**
     * @var Item|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $item;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    protected function setUp()
    {
        if (!interface_exists('Doctrine\Common\Persistence\ObjectManager')) {
            $this->markTestSkipped('Doctrine Common has to be installed for this test to run.');
        }

        $this->repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->product = new Product();
        $this->product->setName('test product');

        $this->item = new Item(
            $this->om,
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

        $this->om->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('OroTestBundle:test'))
            ->will($this->returnValue($this->repository));

        $this->repository->expects($this->any())
            ->method('find')
            ->will($this->returnValue($this->product));
    }

    public function testGetEntityName()
    {
        $this->assertEquals('OroTestBundle:test', $this->item->getEntityName());
    }

    public function testGetRecordId()
    {
        $this->assertEquals(1, $this->item->getRecordId());
    }

    public function testGetEntity()
    {
        $this->om->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('OroTestBundle:test'))
            ->will($this->returnValue($this->repository));

        $this->item->getEntity();
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
