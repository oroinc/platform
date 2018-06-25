<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Query\LazyResult;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LazyResultTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    protected $elements = [];

    /** @var int */
    protected $count = 42;

    /** @var array */
    protected $aggregatedData = [
        'test_name' => [
            'field' => 'test_field_name',
            'function' => Query::AGGREGATE_FUNCTION_COUNT
        ]
    ];

    /** @var Query */
    protected $query;

    /** @var LazyResult */
    protected $result;

    protected function setUp()
    {
        $this->elements = [
            new Item(
                'OroTestBundle:test',
                1,
                'test title first',
                'http://example.com',
                []
            ),
            new Item(
                'OroTestBundle:test',
                2,
                'test title second',
                'http://example.com',
                []
            ),
            new Item(
                'OroTestBundle:test',
                3,
                'test title third',
                'http://example.com',
                []
            )
        ];

        $this->query = new Query();

        $this->result = new LazyResult(
            $this->query,
            $this->getCallbackFunction($this->elements),
            $this->getCallbackFunction($this->count),
            $this->getCallbackFunction($this->aggregatedData)
        );
    }

    public function testGetElements()
    {
        $this->assertSame($this->elements, $this->result->getElements());
    }

    public function testGetRecordsCount()
    {
        $this->assertEquals($this->count, $this->result->getRecordsCount());
    }

    public function testGetAggregatedData()
    {
        $this->assertEquals($this->aggregatedData, $this->result->getAggregatedData());
    }

    public function testToArray()
    {
        $this->assertEquals($this->elements, $this->result->toArray());
    }

    public function testFirst()
    {
        $this->assertEquals($this->elements[0], $this->result->first());
    }

    public function testLast()
    {
        $this->assertEquals($this->elements[2], $this->result->last());
    }

    public function testKey()
    {
        $this->assertEquals(0, $this->result->key());
    }

    public function testNext()
    {
        $this->assertEquals($this->elements[1], $this->result->next());
    }

    public function testCurrent()
    {
        $this->assertEquals($this->elements[0], $this->result->current());
    }

    public function testRemove()
    {
        $this->assertEquals($this->elements[1], $this->result->remove(1));
    }

    public function testRemoveElement()
    {
        $this->assertTrue($this->result->removeElement($this->elements[1]));
    }

    public function testContainsKey()
    {
        $this->assertTrue($this->result->containsKey(2));
    }

    public function testContains()
    {
        $this->assertTrue($this->result->contains($this->elements[2]));
    }

    public function testExists()
    {
        $this->assertTrue(
            $this->result->exists(
                function ($key, $value) {
                    return $value === $this->elements[2];
                }
            )
        );
    }

    public function testIndexOf()
    {
        $this->assertEquals(2, $this->result->indexOf($this->elements[2]));
    }

    public function testGet()
    {
        $this->assertEquals($this->elements[2], $this->result->get(2));
    }

    public function testGetKeys()
    {
        $this->assertEquals([0, 1, 2], $this->result->getKeys());
    }

    public function testGetValues()
    {
        $this->assertEquals($this->elements, $this->result->getValues());
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->result->count());
    }

    public function testSet()
    {
        $item = new Item(
            'OroTestBundle:test',
            4,
            'test title third',
            'http://example.com',
            []
        );

        $this->result->set(4, $item);

        $this->assertSame($item, $this->result->get(4));
    }

    public function testAdd()
    {
        $item = new Item(
            'OroTestBundle:test',
            4,
            'test title third',
            'http://example.com',
            []
        );

        $this->result->add($item);

        $this->assertSame($item, $this->result->get(3));
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->result->isEmpty());
    }

    public function testGetIterator()
    {
        $items = [];
        foreach ($this->result->getIterator() as $item) {
            $items[] = $item;
        }

        $this->assertEquals($this->elements, $items);
    }

    /**
     * @param mixed $value
     * @return \Closure
     */
    protected function getCallbackFunction($value)
    {
        return function () use ($value) {
            return $value;
        };
    }
}
