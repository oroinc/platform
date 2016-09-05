<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DataProviderDecorator;

class DataProviderDecoratorTest extends \PHPUnit_Framework_TestCase
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
     * @expectedExceptionMessage In the data provider "ArrayObject" does not exist method "offsetFlags".
     */
    public function testCallNotExistMethodCallException()
    {
        $this->decorator->offsetFlags();
    }
}
