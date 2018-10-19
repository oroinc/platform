<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockViewCollection;

class BlockViewCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testGet()
    {
        $elements = [1, 'A' => 'a', 2, 'null' => null, 3, 'A2' => 'a', 'zero' => 0];
        $collection = new BlockViewCollection($elements);

        $this->assertSame(2, $collection[1]);
        $this->assertSame('a', $collection['A']);
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: unknown.
     */
    public function testGetUnknown()
    {
        $collection = new BlockViewCollection();
        $collection['unknown'];
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testSet()
    {
        $collection = new BlockViewCollection();
        $collection['exist'] = 'New exist';
    }

    public function testExist()
    {
        $elements = ['exist' => true, 'null' => null];
        $collection = new BlockViewCollection($elements);

        $this->assertTrue(isset($collection['exist']));
        $this->assertFalse(isset($collection['non-exist']));
        $this->assertFalse(isset($collection['null']));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Not supported
     */
    public function testUnset()
    {
        $elements = ['exist' => true];
        $collection = new BlockViewCollection($elements);
        unset($collection['exist']);
    }
}
