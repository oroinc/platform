<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\PHPUnit\Comparator;

use SebastianBergmann\Comparator\Factory;

use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\PHPUnit\Comparator\PartialObjectComparator;

class PartialObjectComparatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Following properties are missing for type "Oro\Bundle\TestFrameworkBundle\Entity\Item": "missing1, missing2"
     */
    public function testInvalidProperties()
    {
        new PartialObjectComparator(
            'Oro\Bundle\TestFrameworkBundle\Entity\Item',
            ['id', 'stringValue', 'booleanValue', 'missing1', 'missing2']
        );
    }

    public function testEqualObjects()
    {
        $expected = new Item();
        $expected->id = 1;
        $expected->stringValue = 'string';
        $expected->booleanValue = true;

        $actual = new Item();
        $actual->id = 1;
        $actual->stringValue = 'string';
        $actual->booleanValue = false;

        $comparator = new PartialObjectComparator(
            'Oro\Bundle\TestFrameworkBundle\Entity\Item',
            ['id', 'stringValue']
        );
        $comparator->setFactory(Factory::getInstance());

        $this->assertTrue($comparator->accepts($expected, $actual));
        $comparator->assertEquals($expected, $actual);
    }

    /**
     * @expectedException SebastianBergmann\Comparator\ComparisonFailure
     */
    public function testNotEqualObjects()
    {
        $expected = new Item();
        $expected->id = 1;
        $expected->stringValue = 'string';
        $expected->booleanValue = true;

        $actual = new Item();
        $actual->id = 1;
        $actual->stringValue = 'string';
        $actual->booleanValue = false;

        $comparator = new PartialObjectComparator(
            'Oro\Bundle\TestFrameworkBundle\Entity\Item',
            ['id', 'stringValue', 'booleanValue']
        );
        $comparator->setFactory(Factory::getInstance());

        $this->assertTrue($comparator->accepts($expected, $actual));
        $comparator->assertEquals($expected, $actual);
    }
}
