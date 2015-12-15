<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\ArrayCollection;

class ArrayCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $elements = [1, 'A' => 'a', 2, 'null' => null, 3, 'A2' => 'a', 'zero' => 0];
        $collection = new ArrayCollection($elements);

        $this->assertSame(2, $collection[1]);
        $this->assertSame('a', $collection['A']);
        $this->assertSame(null, $collection['non-exist']);
    }

    public function testSet()
    {
        $elements = ['exist' => 'Exist'];
        $collection = new ArrayCollection($elements);
        $collection['exist'] = 'New exist';
        $collection['new'] = 'New';

        $this->assertSame('New exist', $collection['exist']);
        $this->assertSame('New', $collection['new']);
    }

    public function testExist()
    {
        $elements = ['exist' => true, 'null' => null];
        $collection = new ArrayCollection($elements);

        $this->assertTrue(isset($collection['exist']));
        $this->assertFalse(isset($collection['non-exist']));
        $this->assertFalse(isset($collection['null']));
    }

    public function testUnset()
    {
        $elements = ['exist' => true];
        $collection = new ArrayCollection($elements);

        $this->assertTrue(isset($collection['exist']));
        unset($collection['exist']);
        $this->assertFalse(isset($collection['exist']));
    }
}
