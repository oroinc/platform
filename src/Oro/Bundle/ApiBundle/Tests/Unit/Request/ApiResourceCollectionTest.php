<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;

class ApiResourceCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        $collection = new ApiResourceCollection();
        $this->assertEquals(0, $collection->count());
        $this->assertAttributeEquals([], 'keys', $collection);
    }

    public function testConstructor()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection([$resource1, $resource2]);
        $this->assertEquals(2, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource1 => true, (string)$resource2 => true],
            'keys',
            $collection
        );
    }

    public function testAdd()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection();
        $collection->add($resource1);
        $collection->add($resource2);
        $collection->add($resource1);

        $this->assertEquals(2, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource1 => true, (string)$resource2 => true],
            'keys',
            $collection
        );
    }

    public function testRemove()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection([$resource1, $resource2]);

        $this->assertSame(
            $resource1,
            $collection->remove(0)
        );
        $this->assertEquals(1, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource2 => true],
            'keys',
            $collection
        );

        $this->assertNull(
            $collection->remove(0)
        );
        $this->assertEquals(1, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource2 => true],
            'keys',
            $collection
        );
    }

    public function testRemoveElement()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection([$resource1, $resource2]);

        $this->assertTrue(
            $collection->removeElement($resource1)
        );
        $this->assertEquals(1, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource2 => true],
            'keys',
            $collection
        );

        $this->assertFalse(
            $collection->removeElement($resource1)
        );
        $this->assertEquals(1, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource2 => true],
            'keys',
            $collection
        );
    }

    public function testSet()
    {
        $resource1 = new ApiResource('Test\Class1');
        $resource2 = new ApiResource('Test\Class2');

        $collection = new ApiResourceCollection([$resource1]);

        $collection->set(0, $resource2);
        $this->assertEquals(1, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource2 => true],
            'keys',
            $collection
        );
        $this->assertSame($resource2, $collection->get(0));

        $collection->set(1, $resource1);
        $this->assertEquals(2, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource2 => true, (string)$resource1 => true],
            'keys',
            $collection
        );
        $this->assertSame($resource1, $collection->get(1));

        $collection->set(0, $resource1);
        $this->assertEquals(1, $collection->count());
        $this->assertAttributeEquals(
            [(string)$resource1 => true],
            'keys',
            $collection
        );
        $this->assertSame($resource1, $collection->get(0));
    }
}
