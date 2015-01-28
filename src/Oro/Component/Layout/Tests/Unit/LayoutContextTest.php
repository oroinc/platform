<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutContext;

class LayoutContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new LayoutContext();
    }

    /**
     * @dataProvider valueDataProvider
     */
    public function testGetSetHas($value)
    {
        $this->assertFalse(
            $this->context->has('test'),
            'Failed asserting that a value does not exist in the context'
        );
        $this->context->set('test', $value);
        $this->assertTrue(
            $this->context->has('test'),
            'Failed asserting that a value exists in the context'
        );
        $this->assertSame(
            $value,
            $this->context->get('test'),
            'Failed asserting that added to the context value equals to the value returned by "get" method'
        );
    }

    public function testHasForUnknownItem()
    {
        $this->assertFalse($this->context->has('test'));
    }

    /**
     * @expectedException \OutOfBoundsException
     * @expectedExceptionMessage Undefined index: test.
     */
    public function testGetUnknownItem()
    {
        $this->context->get('test');
    }

    public function valueDataProvider()
    {
        return [
            [null],
            [123],
            ['val'],
            [[]],
            [[1, 2, 3]],
            [new \stdClass()]
        ];
    }
}
