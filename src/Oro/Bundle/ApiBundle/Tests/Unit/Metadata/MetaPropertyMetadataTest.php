<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;

class MetaPropertyMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testClone()
    {
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');
        $propertyMetadata->setDataType('testDataType');

        $propertyMetadataClone = clone $propertyMetadata;

        $this->assertEquals($propertyMetadata, $propertyMetadataClone);
    }

    public function testToArray()
    {
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');
        $propertyMetadata->setDataType('testDataType');

        $this->assertEquals(
            [
                'name'      => 'testName',
                'data_type' => 'testDataType',
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testToArrayWithRequiredPropertiesOnly()
    {
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');

        $this->assertEquals(
            [
                'name' => 'testName'
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testNameInConstructor()
    {
        $propertyMetadata = new MetaPropertyMetadata('name');
        $this->assertEquals('name', $propertyMetadata->getName());
    }

    public function testName()
    {
        $propertyMetadata = new MetaPropertyMetadata();

        $this->assertNull($propertyMetadata->getName());
        $propertyMetadata->setName('name');
        $this->assertEquals('name', $propertyMetadata->getName());
    }

    public function testDataType()
    {
        $propertyMetadata = new MetaPropertyMetadata();

        $this->assertNull($propertyMetadata->getDataType());
        $propertyMetadata->setDataType('dataType');
        $this->assertEquals('dataType', $propertyMetadata->getDataType());
    }
}
