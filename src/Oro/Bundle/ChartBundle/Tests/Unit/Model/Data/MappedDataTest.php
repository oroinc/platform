<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model\Data;

use Oro\Bundle\ChartBundle\Model\Data\DataInterface;
use Oro\Bundle\ChartBundle\Model\Data\MappedData;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class MappedDataTest extends \PHPUnit\Framework\TestCase
{
    public function testToArraySourceHasNoMappedField()
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->expectExceptionMessage(
            'Can\'t get a way to read the property "unknownProperty" in class "'
        );

        $mapping = ['label' => 'unknownProperty'];
        $source = $this->createMock(DataInterface::class);
        $sourceData = [$this->createMock(MappedData::class)];
        $source->expects($this->once())
            ->method('toArray')
            ->willReturn($sourceData);
        $mappedData = new MappedData($mapping, $source);
        $actual = $mappedData->toArray();
        $this->assertEquals([], $actual);
    }

    public function testToArrayIfSourceIsArray()
    {
        $mapping = ['label' => 'firstName', 'value' => 'totalAmount'];
        $source = $this->createMock(DataInterface::class);
        $name = 'John';
        $amount = 10;
        $secondName = 'Alex';
        $secondAmount = 42;
        $sourceData = [
            ['firstName' => $name, 'totalAmount' => $amount],
            ['firstName' => $secondName, 'totalAmount' => $secondAmount]
        ];
        $expected = [
            ['label' => $name, 'value' => $amount],
            ['label' => $secondName, 'value' => $secondAmount]
        ];
        $source->expects($this->once())
            ->method('toArray')
            ->willReturn($sourceData);
        $mappedData = new MappedData($mapping, $source);

        $this->assertEquals($expected, $mappedData->toArray());
    }

    public function testToArrayIfSourceIsNestedArray()
    {
        $mapping = ['label' => 'firstName', 'value' => 'totalAmount'];
        $source = $this->createMock(DataInterface::class);
        $name = 'John';
        $amount = 10;
        $secondName = 'Alex';
        $secondAmount = 42;
        $sourceData = [
            'first'  => [['firstName' => $name, 'totalAmount' => $amount]],
            'second' => [['firstName' => $secondName, 'totalAmount' => $secondAmount]]
        ];
        $expected = [
            'first'  => [['label' => $name, 'value' => $amount]],
            'second' => [['label' => $secondName, 'value' => $secondAmount]]
        ];
        $source->expects($this->once())
            ->method('toArray')
            ->willReturn($sourceData);
        $mappedData = new MappedData($mapping, $source);

        $this->assertEquals($expected, $mappedData->toArray());
    }

    public function testToArrayIfSourceIsObject()
    {
        $mapping = ['label' => 'firstName', 'value' => 'totalAmount'];
        $source = $this->createMock(DataInterface::class);
        $name = 'John';
        $amount = 10;
        $secondName = 'Alex';
        $secondAmount = 42;
        $sourceData = [
            $this->constructSourceRow($name, $amount),
            $this->constructSourceRow($secondName, $secondAmount)
        ];
        $expected = [
            ['label' => $name, 'value' => $amount],
            ['label' => $secondName, 'value' => $secondAmount]
        ];
        $source->expects($this->once())
            ->method('toArray')
            ->willReturn($sourceData);
        $mappedData = new MappedData($mapping, $source);

        $this->assertEquals($expected, $mappedData->toArray());
    }

    private function constructSourceRow(string $firstName, string $totalAmount): \stdClass
    {
        $sourceRow = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getFirstName', 'getTotalAmount'])
            ->getMock();
        $sourceRow->expects($this->once())
            ->method('getFirstName')
            ->willReturn($firstName);
        $sourceRow->expects($this->once())
            ->method('getTotalAmount')
            ->willReturn($totalAmount);

        return $sourceRow;
    }
}
