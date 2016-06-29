<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;

class ApiResourceCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testAddAndCountableAndIterator()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection();
        $collection->add($resource1);
        $collection->add($resource2);

        $this->assertEquals(2, $collection->count());
        $this->assertCount(2, $collection);
        $this->assertEquals(
            ['Test\Class1' => $resource1, 'Test\Class2' => $resource2],
            $collection->toArray()
        );
        $this->assertEquals(
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

        $this->assertSame(
            $resource1,
            $collection->remove('Test\Class1')
        );
        $this->assertCount(1, $collection);

        $this->assertNull(
            $collection->remove('Test\Class1')
        );
        $this->assertCount(1, $collection);
        $this->assertSame(
            $resource2,
            $collection->get('Test\Class2')
        );
    }

    public function testHas()
    {
        $collection = new ApiResourceCollection();
        $collection->add(new ApiResource('Test\Class1'));
        $collection->add(new ApiResource('Test\Class2'));

        $this->assertTrue($collection->has('Test\Class1'));
        $this->assertTrue($collection->has('Test\Class2'));
        $this->assertFalse($collection->has('Test\Class3'));
    }

    public function testGet()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection();
        $collection->add($resource1);
        $collection->add($resource2);

        $this->assertSame($resource1, $collection->get('Test\Class1'));
        $this->assertSame($resource2, $collection->get('Test\Class2'));
        $this->assertNull($collection->get('Test\Class3'));
    }

    public function testIsEmptyAndClear()
    {
        $collection = new ApiResourceCollection();
        $this->assertTrue($collection->isEmpty());

        $collection->add(new ApiResource('Test\Class1'));
        $this->assertFalse($collection->isEmpty());

        $collection->clear();
        $this->assertTrue($collection->isEmpty());
    }
}
