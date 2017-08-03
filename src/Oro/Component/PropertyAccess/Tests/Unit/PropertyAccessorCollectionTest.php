<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\Car;

abstract class PropertyAccessorCollectionTest extends PropertyAccessorArrayAccessTest
{
    public function testSetValueCallsAdderAndRemoverForCollections()
    {
        $axesBefore     = $this->getContainer(array(1 => 'second', 3 => 'fourth', 4 => 'fifth'));
        $axesMerged     = $this->getContainer(array(1 => 'first', 2 => 'second', 3 => 'third'));
        $axesAfter      = $this->getContainer(array(1 => 'second', 5 => 'first', 6 => 'third'));
        $axesMergedCopy = is_object($axesMerged) ? clone $axesMerged : $axesMerged;

        // Don't use a mock in order to test whether the collections are
        // modified while iterating them
        $car = new Car($axesBefore);

        $this->propertyAccessor->setValue($car, 'axes', $axesMerged);

        $this->assertEquals($axesAfter, $car->getAxes());

        // The passed collection was not modified
        $this->assertEquals($axesMergedCopy, $axesMerged);
    }

    public function testAddValueToCollections()
    {
        $axesBefore     = $this->getContainer(array(1 => 'second', 3 => 'fourth', 4 => 'fifth'));
        $axesMerged     = $this->getContainer(array(1 => 'first', 2 => 'second', 3 => 'third'));
        $axesAfter      = $this->getContainer(array(1 => 'second', 3 => 'fourth', 4 => 'fifth', 5 => 'first'));
        $axesMergedCopy = is_object($axesMerged) ? clone $axesMerged : $axesMerged;

        // Don't use a mock in order to test whether the collections are
        // modified while iterating them
        $car = new Car($axesBefore);

        $this->propertyAccessor->setValue($car, 'axes', 'first');

        $this->assertEquals($axesAfter, $car->getAxes());

        // The passed collection was not modified
        $this->assertEquals($axesMergedCopy, $axesMerged);
    }

    public function testSetValueCallsAdderAndRemoverForNestedCollections()
    {
        $car        = $this->createMock('Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CompositeCar');
        $structure  = $this->createMock('Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarStructure');
        $axesBefore = $this->getContainer(array(1 => 'second', 3 => 'fourth'));
        $axesAfter  = $this->getContainer(array(0 => 'first', 1 => 'second', 2 => 'third'));

        $car->expects($this->any())
            ->method('getStructure')
            ->will($this->returnValue($structure));

        $structure->expects($this->at(0))
            ->method('getAxes')
            ->will($this->returnValue($axesBefore));
        $structure->expects($this->at(1))
            ->method('getAxes')
            ->will($this->returnValue($axesBefore));
        $structure->expects($this->at(2))
            ->method('removeAxis')
            ->with('fourth');
        $structure->expects($this->at(3))
            ->method('addAxis')
            ->with('first');
        $structure->expects($this->at(4))
            ->method('addAxis')
            ->with('third');

        $this->propertyAccessor->setValue($car, 'structure.axes', $axesAfter);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage Neither the property "axes" nor one of the methods "addAx()"/"removeAx()", "addAxe()"/"removeAxe()", "addAxis()"/"removeAxis()", "setAxes()", "axes()", "__set()" or "__call()" exist and have public access in class "Mock_CarNoAdderAndRemover
     */
    // @codingStandardsIgnoreEnd
    public function testSetValueFailsIfNoAdderNorRemoverFound()
    {
        $car        = $this->createMock('Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarNoAdderAndRemover');
        $axesBefore = $this->getContainer(array(1 => 'second', 3 => 'fourth'));
        $axesAfter  = $this->getContainer(array(0 => 'first', 1 => 'second', 2 => 'third'));

        $car->expects($this->any())
            ->method('getAxes')
            ->will($this->returnValue($axesBefore));

        $this->propertyAccessor->setValue($car, 'axes', $axesAfter);
    }

    public function testIsWritableReturnsTrueIfAdderAndRemoverExists()
    {
        $car = $this->createMock('Oro\Component\PropertyAccess\Tests\Unit\Fixtures\Car');
        $axes = $this->getContainer(array(1 => 'first', 2 => 'second', 3 => 'third'));

        $this->assertTrue($this->propertyAccessor->isWritable($car, 'axes', $axes));
    }

    public function testIsWritableReturnsFalseIfOnlyAdderExists()
    {
        $car = $this->createMock('Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarOnlyAdder');
        $axes = $this->getContainer(array(1 => 'first', 2 => 'second', 3 => 'third'));

        $this->assertFalse($this->propertyAccessor->isWritable($car, 'axes', $axes));
    }

    public function testIsWritableReturnsFalseIfOnlyRemoverExists()
    {
        $car = $this->createMock('Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarOnlyRemover');
        $axes = $this->getContainer(array(1 => 'first', 2 => 'second', 3 => 'third'));

        $this->assertFalse($this->propertyAccessor->isWritable($car, 'axes', $axes));
    }

    public function testIsWritableReturnsFalseIfNoAdderNorRemoverExists()
    {
        $car = $this->createMock('Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarNoAdderAndRemover');
        $axes = $this->getContainer(array(1 => 'first', 2 => 'second', 3 => 'third'));

        $this->assertFalse($this->propertyAccessor->isWritable($car, 'axes', $axes));
    }
}
