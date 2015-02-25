<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator\Condition;

use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;

class ConditionCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldValidateConstructParameters()
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionInterface",'
            . ' "stdClass" given.'
        );
        new ConditionCollection([new \stdClass()]);
    }

    public function testShouldAcceptValidConditionsAsConstructorParameters()
    {
        $collection = new ConditionCollection(
            [$this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionInterface')]
        );

        $this->assertNotEmpty($collection);
    }

    public function testAppendShouldValidateParameter()
    {
        $this->setExpectedException(
            '\Oro\Component\Layout\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionInterface",'
            . ' "stdClass" given.'
        );

        $collection = new ConditionCollection();
        $collection->append(new \stdClass());
    }

    public function testAppendShouldAcceptValidCondition()
    {
        $collection = new ConditionCollection();
        $this->assertEmpty($collection);

        $collection->append($this->getMock('Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionInterface'));

        $this->assertNotEmpty($collection);
    }
}
