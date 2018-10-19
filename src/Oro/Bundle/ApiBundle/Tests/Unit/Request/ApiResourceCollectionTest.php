<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;

class ApiResourceCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testAddAndCountableAndIterator()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection();
        $collection->add($resource1);
        $collection->add($resource2);

        self::assertEquals(2, $collection->count());
        self::assertCount(2, $collection);
        self::assertEquals(
            ['Test\Class1' => $resource1, 'Test\Class2' => $resource2],
            $collection->toArray()
        );
        self::assertEquals(
            ['Test\Class1' => $resource1, 'Test\Class2' => $resource2],
            iterator_to_array($collection)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage A resource for "Test\Class1" already exists.
     */
    public function testAddAlreadyExisting()
    {
        $collection = new ApiResourceCollection();
        $collection->add(new ApiResource('Test\Class1'));
        $collection->add(new ApiResource('Test\Class1'));
    }

    public function testRemove()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection();
        $collection->add($resource1);
        $collection->add($resource2);

        self::assertSame(
            $resource1,
            $collection->remove('Test\Class1')
        );
        self::assertCount(1, $collection);

        self::assertNull(
            $collection->remove('Test\Class1')
        );
        self::assertCount(1, $collection);
        self::assertSame(
            $resource2,
            $collection->get('Test\Class2')
        );
    }

    public function testHas()
    {
        $collection = new ApiResourceCollection();
        $collection->add(new ApiResource('Test\Class1'));
        $collection->add(new ApiResource('Test\Class2'));

        self::assertTrue($collection->has('Test\Class1'));
        self::assertTrue($collection->has('Test\Class2'));
        self::assertFalse($collection->has('Test\Class3'));
    }

    public function testGet()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection();
        $collection->add($resource1);
        $collection->add($resource2);

        self::assertSame($resource1, $collection->get('Test\Class1'));
        self::assertSame($resource2, $collection->get('Test\Class2'));
        self::assertNull($collection->get('Test\Class3'));
    }

    public function testIsEmptyAndClear()
    {
        $collection = new ApiResourceCollection();
        self::assertTrue($collection->isEmpty());

        $collection->add(new ApiResource('Test\Class1'));
        self::assertFalse($collection->isEmpty());

        $collection->clear();
        self::assertTrue($collection->isEmpty());
    }
}
