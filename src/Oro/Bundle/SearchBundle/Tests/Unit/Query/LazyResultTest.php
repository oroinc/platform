<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Query\LazyResult;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LazyResultTest extends TestCase
{
    private array $elements = [];

    private int $count = 42;

    private array $aggregatedData = [
        'test_name' => [
            'field' => 'test_field_name',
            'function' => Query::AGGREGATE_FUNCTION_COUNT
        ]
    ];

    private LazyResult $result;

    #[\Override]
    protected function setUp(): void
    {
        $this->elements = [
            new Item(
                'OroTestBundle:test',
                1,
                'http://example.com',
                []
            ),
            new Item(
                'OroTestBundle:test',
                2,
                'http://example.com',
                []
            ),
            new Item(
                'OroTestBundle:test',
                3,
                'http://example.com',
                []
            )
        ];

        $this->result = new LazyResult(
            new Query(),
            $this->getCallbackFunction($this->elements),
            $this->getCallbackFunction($this->count),
            $this->getCallbackFunction($this->aggregatedData)
        );
    }

    public function testGetElements(): void
    {
        $this->assertSame($this->elements, $this->result->getElements());
    }

    public function testGetRecordsCount(): void
    {
        $this->assertEquals($this->count, $this->result->getRecordsCount());
    }

    public function testGetAggregatedData(): void
    {
        $this->assertEquals($this->aggregatedData, $this->result->getAggregatedData());
    }

    public function testToArray(): void
    {
        $this->assertEquals($this->elements, $this->result->toArray());
    }

    public function testFirst(): void
    {
        $this->assertEquals($this->elements[0], $this->result->first());
    }

    public function testLast(): void
    {
        $this->assertEquals($this->elements[2], $this->result->last());
    }

    public function testKey(): void
    {
        $this->assertEquals(0, $this->result->key());
    }

    public function testNext(): void
    {
        $this->assertEquals($this->elements[1], $this->result->next());
    }

    public function testCurrent(): void
    {
        $this->assertEquals($this->elements[0], $this->result->current());
    }

    public function testRemove(): void
    {
        $this->assertEquals($this->elements[1], $this->result->remove(1));
    }

    public function testRemoveElement(): void
    {
        $this->assertTrue($this->result->removeElement($this->elements[1]));
    }

    public function testContainsKey(): void
    {
        $this->assertTrue($this->result->containsKey(2));
    }

    public function testContains(): void
    {
        $this->assertTrue($this->result->contains($this->elements[2]));
    }

    public function testExists(): void
    {
        $this->assertTrue(
            $this->result->exists(
                function ($key, $value) {
                    return $value === $this->elements[2];
                }
            )
        );
    }

    public function testIndexOf(): void
    {
        $this->assertEquals(2, $this->result->indexOf($this->elements[2]));
    }

    public function testGet(): void
    {
        $this->assertEquals($this->elements[2], $this->result->get(2));
    }

    public function testGetKeys(): void
    {
        $this->assertEquals([0, 1, 2], $this->result->getKeys());
    }

    public function testGetValues(): void
    {
        $this->assertEquals($this->elements, $this->result->getValues());
    }

    public function testCount(): void
    {
        $this->assertEquals(3, $this->result->count());
    }

    public function testSet(): void
    {
        $item = new Item(
            'OroTestBundle:test',
            4,
            'http://example.com',
            []
        );

        $this->result->set(4, $item);

        $this->assertSame($item, $this->result->get(4));
    }

    public function testAdd(): void
    {
        $item = new Item(
            'OroTestBundle:test',
            4,
            'http://example.com',
            []
        );

        $this->result->add($item);

        $this->assertSame($item, $this->result->get(3));
    }

    public function testIsEmpty(): void
    {
        $this->assertFalse($this->result->isEmpty());
    }

    public function testGetIterator(): void
    {
        $items = [];
        foreach ($this->result->getIterator() as $item) {
            $items[] = $item;
        }

        $this->assertEquals($this->elements, $items);
    }

    private function getCallbackFunction(mixed $value): \Closure
    {
        return function () use ($value) {
            return $value;
        };
    }
}
