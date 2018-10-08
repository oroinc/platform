<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\MappedData;

class MappedDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage Neither the property "unknownProperty" nor one of the methods "getUnknownProperty()",
     * "isUnknownProperty()", "hasUnknownProperty()", "__get()" or "__call()" exist and have public access in class
     * "dataSourceClassName".
     */
    public function testToArraySourceHasNoMappedField()
    {
        $mapping = array('label' => 'unknownProperty');
        $source = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
        $sourceData = array($this->createMock(MappedData::class));
        $source->expects($this->once())->method('toArray')->will($this->returnValue($sourceData));
        $mappedData = new MappedData($mapping, $source);
        $actual = $mappedData->toArray();
        $this->assertEquals($actual, array());
    }

    public function testToArrayIfSourceIsArray()
    {
        $mapping = array('label' => 'firstName', 'value' => 'totalAmount');
        $source = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
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

    public function testToArrayIfSourceIsNestedArray()
    {
        $mapping = array('label' => 'firstName', 'value' => 'totalAmount');
        $source = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
        $name = 'John';
        $amount = 10;
        $secondName = 'Alex';
        $secondAmount = 42;
        $sourceData = array(
            'first'  => array(array('firstName' => $name, 'totalAmount' => $amount)),
            'second' => array(array('firstName' => $secondName, 'totalAmount' => $secondAmount))
        );
        $expected = array(
            'first'  => array(array('label' => $name, 'value' => $amount)),
            'second' => array(array('label' => $secondName, 'value' => $secondAmount))
        );
        $source->expects($this->once())->method('toArray')->will($this->returnValue($sourceData));
        $mappedData = new MappedData($mapping, $source);

        $this->assertEquals($expected, $mappedData->toArray());
    }

    public function testToArrayIfSourceIsObject()
    {
        $mapping = array('label' => 'firstName', 'value' => 'totalAmount');
        $source = $this->createMock('Oro\Bundle\ChartBundle\Model\Data\DataInterface');
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
        $sourceRow = $this->getMockBuilder('stdClass')->setMethods($methods)->getMock();

        $sourceRow->expects($this->once())->method('getFirstName')->will($this->returnValue($firstName));
        $sourceRow->expects($this->once())->method('getTotalAmount')->will($this->returnValue($totalAmount));

        return $sourceRow;
    }
}
