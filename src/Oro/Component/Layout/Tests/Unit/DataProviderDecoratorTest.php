<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DataProviderDecorator;

class DataProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \ArrayObject
     */
    protected $decorator;

    protected function setUp(): void
    {
        $this->decorator = new DataProviderDecorator(new \ArrayObject(), ['offset']);
    }

    public function testInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new DataProviderDecorator(null, []);
    }

    public function testCall()
    {
        $value = 'value';

        $this->decorator->offsetSet(0, $value);

        $this->assertEquals($value, $this->decorator->offsetGet(0));
    }

    public function testCallBadMethodCallException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->decorator->getFlags();
    }

    public function testCallNotExistMethodCallException()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Method "offsetFlags" not found in "ArrayObject".');

        $this->decorator->offsetFlags();
    }
}
