<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DataProviderDecorator;

class DataProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \ArrayObject
     */
    protected $decorator;

    protected function setUp()
    {
        $this->decorator = new DataProviderDecorator(new \ArrayObject(), ['offset']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentException()
    {
        new DataProviderDecorator(null, []);
    }

    public function testCall()
    {
        $value = 'value';

        $this->decorator->offsetSet(0, $value);

        $this->assertEquals($value, $this->decorator->offsetGet(0));
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testCallBadMethodCallException()
    {
        $this->decorator->getFlags();
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Method "offsetFlags" not found in "ArrayObject".
     */
    public function testCallNotExistMethodCallException()
    {
        $this->decorator->offsetFlags();
    }
}
