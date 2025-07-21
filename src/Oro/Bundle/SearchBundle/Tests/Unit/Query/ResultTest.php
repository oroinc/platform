<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Product;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    private array $items = [];

    private Result $result;
    private Result $result1;

    private static array $aggregatedData = [
        'test_name' => [
            'field' => 'test_field_name',
            'function' => Query::AGGREGATE_FUNCTION_COUNT
        ]
    ];

    #[\Override]
    protected function setUp(): void
    {
        $product = new Product();
        $product->setName('test product');

        $this->items[] = new Item(
            'OroTestBundle:test',
            1,
            'http://example.com',
            [],
            [
                 'alias' => 'test_product',
                 'label' => 'test product',
                 'fields' => [
                     [
                         'name'          => 'name',
                         'target_type'   => 'text',
                     ],
                 ],
            ]
        );
        $this->items[] = new Item(
            'OroTestBundle:test',
            2,
            'http://example.com',
            [],
            [
                 'alias' => 'test_product',
                 'label' => 'test product',
                 'fields' => [
                     [
                         'name'          => 'name',
                         'target_type'   => 'text',
                     ],
                 ],
            ]
        );
        $this->items[] = new Item(
            'OroTestBundle:test',
            3,
            'http://example.com',
            [],
            [
                 'alias' => 'test_product',
                 'label' => 'test product',
                 'fields' => [
                     [
                         'name'          => 'name',
                         'target_type'   => 'text',
                     ],
                 ],
            ]
        );

        $query = new Query();
        $query->from(['OroTestBundle:test', 'OroTestBundle:product']);
        $query->getCriteria()->where(Criteria::expr()->contains(
            Criteria::implodeFieldTypeName(Query::TYPE_TEXT, 'name'),
            'test string'
        ));

        $this->result = new Result($query, $this->items, 3, self::$aggregatedData);
        $this->result1 = new Result($query, [], 0);
    }

    public function testGetQuery(): void
    {
        $query = $this->result->getQuery();
        $from = $query->getFrom();

        $this->assertEquals('OroTestBundle:test', $from[0]);
        $this->assertEquals('OroTestBundle:product', $from[1]);

        $whereExpression = $query->getCriteria()->getWhereExpression();
        $this->assertInstanceOf(\Doctrine\Common\Collections\Expr\Comparison::class, $whereExpression);
        $this->assertEquals('text.name', $whereExpression->getField());
        $this->assertEquals(Comparison::CONTAINS, $whereExpression->getOperator());
        $this->assertEquals('test string', $whereExpression->getValue()->getValue());
    }

    public function testGetRecordsCount(): void
    {
        $this->assertEquals(3, $this->result->getRecordsCount());
    }

    public function testToSearchResultData(): void
    {
        $resultArray = $this->result->toSearchResultData();
        $this->assertEquals(3, $resultArray['records_count']);
        $this->assertEquals(3, $resultArray['count']);
        $this->assertEquals('OroTestBundle:test', $resultArray['data'][0]['entity_name']);
        $this->assertEquals(2, $resultArray['data'][1]['record_id']);

        $this->result1->toSearchResultData();
    }

    public function testGetAggregatedData(): void
    {
        $this->assertSame(self::$aggregatedData, $this->result->getAggregatedData());
        $this->assertSame([], $this->result1->getAggregatedData());
    }

    public function testToArray(): void
    {
        $this->assertEquals($this->items, $this->result->toArray());
    }
}
