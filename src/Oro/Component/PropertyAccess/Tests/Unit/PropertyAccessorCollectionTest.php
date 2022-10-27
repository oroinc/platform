<?php

namespace Oro\Component\PropertyAccess\Tests\Unit;

use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\Car;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarNoAdderAndRemover;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarOnlyAdder;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarOnlyRemover;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CarStructure;
use Oro\Component\PropertyAccess\Tests\Unit\Fixtures\CompositeCar;

abstract class PropertyAccessorCollectionTest extends PropertyAccessorArrayAccessTest
{
    public function testSetValueCallsAdderAndRemoverForCollections()
    {
        $axesBefore     = $this->getContainer([1 => 'second', 3 => 'fourth', 4 => 'fifth']);
        $axesMerged     = $this->getContainer([1 => 'first', 2 => 'second', 3 => 'third']);
        $axesAfter      = $this->getContainer([1 => 'second', 5 => 'first', 6 => 'third']);
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
        $axesBefore     = $this->getContainer([1 => 'second', 3 => 'fourth', 4 => 'fifth']);
        $axesMerged     = $this->getContainer([1 => 'first', 2 => 'second', 3 => 'third']);
        $axesAfter      = $this->getContainer([1 => 'second', 3 => 'fourth', 4 => 'fifth', 5 => 'first']);
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
        $car        = $this->createMock(CompositeCar::class);
        $structure  = $this->createMock(CarStructure::class);
        $axesBefore = $this->getContainer([1 => 'second', 3 => 'fourth']);
        $axesAfter  = $this->getContainer([0 => 'first', 1 => 'second', 2 => 'third']);

        $car->expects($this->any())
            ->method('getStructure')
            ->willReturn($structure);

        $structure->expects($this->exactly(2))
            ->method('getAxes')
            ->willReturnOnConsecutiveCalls($axesBefore, $axesBefore);
        $structure->expects($this->once())
            ->method('removeAxis')
            ->with('fourth');
        $structure->expects($this->exactly(2))
            ->method('addAxis')
            ->withConsecutive(['first'], ['third']);

        $this->propertyAccessor->setValue($car, 'structure.axes', $axesAfter);
    }

    public function testSetValueFailsIfNoAdderNorRemoverFound()
    {
        $this->expectException(\Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException::class);
        $this->expectExceptionMessage(
            'Neither the property "axes" nor one of the methods "addAx()"/"removeAx()", "addAxe()"/"removeAxe()",'
            . ' "addAxis()"/"removeAxis()", "setAxes()", "axes()", "__set()" or "__call()" exist'
            . ' and have public access in class "Mock_CarNoAdderAndRemover'
        );

        $car        = $this->createMock(CarNoAdderAndRemover::class);
        $axesBefore = $this->getContainer([1 => 'second', 3 => 'fourth']);
        $axesAfter  = $this->getContainer([0 => 'first', 1 => 'second', 2 => 'third']);

        $car->expects($this->any())
            ->method('getAxes')
            ->willReturn($axesBefore);

        $this->propertyAccessor->setValue($car, 'axes', $axesAfter);
    }

    public function testIsWritableReturnsTrueIfAdderAndRemoverExists()
    {
        $car = $this->createMock(Car::class);
        $axes = $this->getContainer([1 => 'first', 2 => 'second', 3 => 'third']);

        $this->assertTrue($this->propertyAccessor->isWritable($car, 'axes', $axes));
    }

    public function testIsWritableReturnsFalseIfOnlyAdderExists()
    {
        $car = $this->createMock(CarOnlyAdder::class);
        $axes = $this->getContainer([1 => 'first', 2 => 'second', 3 => 'third']);

        $this->assertFalse($this->propertyAccessor->isWritable($car, 'axes', $axes));
    }

    public function testIsWritableReturnsFalseIfOnlyRemoverExists()
    {
        $car = $this->createMock(CarOnlyRemover::class);
        $axes = $this->getContainer([1 => 'first', 2 => 'second', 3 => 'third']);

        $this->assertFalse($this->propertyAccessor->isWritable($car, 'axes', $axes));
    }

    public function testIsWritableReturnsFalseIfNoAdderNorRemoverExists()
    {
        $car = $this->createMock(CarNoAdderAndRemover::class);
        $axes = $this->getContainer([1 => 'first', 2 => 'second', 3 => 'third']);

        $this->assertFalse($this->propertyAccessor->isWritable($car, 'axes', $axes));
    }
}
