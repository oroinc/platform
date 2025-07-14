<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Visitor;

use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;
use PHPUnit\Framework\TestCase;

class VisitorCollectionTest extends TestCase
{
    public function testShouldValidateConstructParameters(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\Loader\Visitor\VisitorInterface",'
            . ' "stdClass" given.'
        );
        new VisitorCollection([new \stdClass()]);
    }

    public function testShouldAcceptValidConditionsAsConstructorParameters(): void
    {
        $collection = new VisitorCollection(
            [$this->createMock(VisitorInterface::class)]
        );

        $this->assertNotEmpty($collection);
    }

    public function testAppendShouldValidateParameter(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Component\Layout\Loader\Visitor\VisitorInterface",'
            . ' "stdClass" given.'
        );

        $collection = new VisitorCollection();
        $collection->append(new \stdClass());
    }

    public function testAppendShouldAcceptValidCondition(): void
    {
        $collection = new VisitorCollection();
        $this->assertEmpty($collection);

        $collection->append($this->createMock(VisitorInterface::class));

        $this->assertNotEmpty($collection);
    }
}
