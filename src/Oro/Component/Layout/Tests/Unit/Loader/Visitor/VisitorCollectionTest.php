<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Visitor;

use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

class VisitorCollectionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldValidateConstructParameters()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\Loader\Visitor\VisitorInterface",'
            . ' "stdClass" given.'
        );
        new VisitorCollection([new \stdClass()]);
    }

    public function testShouldAcceptValidConditionsAsConstructorParameters()
    {
        $collection = new VisitorCollection(
            [$this->createMock(VisitorInterface::class)]
        );

        $this->assertNotEmpty($collection);
    }

    public function testAppendShouldValidateParameter()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\Loader\Visitor\VisitorInterface",'
            . ' "stdClass" given.'
        );

        $collection = new VisitorCollection();
        $collection->append(new \stdClass());
    }

    public function testAppendShouldAcceptValidCondition()
    {
        $collection = new VisitorCollection();
        $this->assertEmpty($collection);

        $collection->append($this->createMock(VisitorInterface::class));

        $this->assertNotEmpty($collection);
    }
}
