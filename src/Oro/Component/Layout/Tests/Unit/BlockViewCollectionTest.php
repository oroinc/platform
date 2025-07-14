<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockViewCollection;
use PHPUnit\Framework\TestCase;

class BlockViewCollectionTest extends TestCase
{
    public function testGet(): void
    {
        $elements = [1, 'A' => 'a', 2, 'null' => null, 3, 'A2' => 'a', 'zero' => 0];
        $collection = new BlockViewCollection($elements);

        $this->assertSame(2, $collection[1]);
        $this->assertSame('a', $collection['A']);
    }

    public function testGetUnknown(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Undefined index: unknown.');

        $collection = new BlockViewCollection();
        $collection['unknown'];
    }

    public function testSet(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        $collection = new BlockViewCollection();
        $collection['exist'] = 'New exist';
    }

    public function testExist(): void
    {
        $elements = ['exist' => true, 'null' => null];
        $collection = new BlockViewCollection($elements);

        $this->assertTrue(isset($collection['exist']));
        $this->assertFalse(isset($collection['non-exist']));
        $this->assertFalse(isset($collection['null']));
    }

    public function testUnset(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Not supported');

        $elements = ['exist' => true];
        $collection = new BlockViewCollection($elements);
        unset($collection['exist']);
    }
}
