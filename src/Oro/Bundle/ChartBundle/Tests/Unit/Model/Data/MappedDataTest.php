<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\MappedData;

class MappedDataTest extends \PHPUnit_Framework_TestCase
{
    public function testToArrayIfSourceIsArray()
    {
        $mapping = array('label' => 'firstName', 'value' => 'totalAmount');
        $source = $this->getMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
        $name = 'John';
        $amount = 10;
        $secondName = 'Alex';
        $secondAmount = 42;
        $sourceData = array(
            array('firstName' => $name, 'totalAmount' => $amount),
            array('firstName' => $secondName, 'totalAmount' => $secondAmount)
        );
        $expected = array(
            array('label' => $name, 'value' => $amount),
            array('label' => $secondName, 'value' => $secondAmount)
        );
        $source->expects($this->once())->method('toArray')->will($this->returnValue($sourceData));
        $mappedData = new MappedData($mapping, $source);

        $this->assertEquals($expected, $mappedData->toArray());
    }

    public function testToArrayIfSourceIsObject()
    {
        $mapping = array('label' => 'firstName', 'value' => 'totalAmount');
        $source = $this->getMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
        $name = 'John';
        $amount = 10;
        $secondName = 'Alex';
        $secondAmount = 42;
        $sourceData = array(
            $this->constructSourceRow($name, $amount),
            $this->constructSourceRow($secondName, $secondAmount)
        );
        $expected = array(
            array('label' => $name, 'value' => $amount),
            array('label' => $secondName, 'value' => $secondAmount)
        );
        $source->expects($this->once())->method('toArray')->will($this->returnValue($sourceData));
        $mappedData = new MappedData($mapping, $source);

        $this->assertEquals($expected, $mappedData->toArray());
    }

    protected function constructSourceRow($firstName, $totalAmount)
    {
        $methods = array(
            'getFirstName',
            'getTotalAmount'
        );
        $sourceRow = $this->getMock('stdClass', $methods);

        $sourceRow->expects($this->once())->method('getFirstName')->will($this->returnValue($firstName));
        $sourceRow->expects($this->once())->method('getTotalAmount')->will($this->returnValue($totalAmount));

        return $sourceRow;
    }
}
