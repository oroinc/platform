<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Generator\Visitor;

use Oro\Component\Layout\Extension\Theme\Generator\Visitor\VisitorCollection;

class VisitorCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldValidateConstructParameters()
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Component\Layout\Extension\Theme\Generator\Visitor\VisitorInterface",'
            . ' "stdClass" given.'
        );
        new VisitorCollection([new \stdClass()]);
    }

    public function testShouldAcceptValidConditionsAsConstructorParameters()
    {
        $collection = new VisitorCollection(
            [$this->getMock('Oro\Component\Layout\Extension\Theme\Generator\Visitor\VisitorInterface')]
        );

        $this->assertNotEmpty($collection);
    }

    public function testAppendShouldValidateParameter()
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Component\Layout\Extension\Theme\Generator\Visitor\VisitorInterface",'
            . ' "stdClass" given.'
        );

        $collection = new VisitorCollection();
        $collection->append(new \stdClass());
    }

    public function testAppendShouldAcceptValidCondition()
    {
        $collection = new VisitorCollection();
        $this->assertEmpty($collection);

        $collection->append($this->getMock('Oro\Component\Layout\Extension\Theme\Generator\Visitor\VisitorInterface'));

        $this->assertNotEmpty($collection);
    }
}
