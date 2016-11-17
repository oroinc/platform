<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class MetaPropertyMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testClone()
    {
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');
        $propertyMetadata->setPropertyPath('testPropertyPath');
        $propertyMetadata->setDataType('testDataType');

        $propertyMetadataClone = clone $propertyMetadata;

        $this->assertEquals($propertyMetadata, $propertyMetadataClone);
    }

    public function testToArray()
    {
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');
        $propertyMetadata->setPropertyPath('testPropertyPath');
        $propertyMetadata->setDataType('testDataType');

        $this->assertEquals(
            [
                'name'          => 'testName',
                'property_path' => 'testPropertyPath',
                'data_type'     => 'testDataType',
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

    public function testPropertyPath()
    {
        $propertyMetadata = new MetaPropertyMetadata();

        $this->assertNull($propertyMetadata->getPropertyPath());
        $propertyMetadata->setName('name');
        $this->assertEquals('name', $propertyMetadata->getPropertyPath());
        $propertyMetadata->setPropertyPath('propertyPath');
        $this->assertEquals('propertyPath', $propertyMetadata->getPropertyPath());
        $propertyMetadata->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        $this->assertNull($propertyMetadata->getPropertyPath());
        $propertyMetadata->setPropertyPath(null);
        $this->assertEquals('name', $propertyMetadata->getPropertyPath());
    }

    public function testDataType()
    {
        $propertyMetadata = new MetaPropertyMetadata();

        $this->assertNull($propertyMetadata->getDataType());
        $propertyMetadata->setDataType('dataType');
        $this->assertEquals('dataType', $propertyMetadata->getDataType());
    }

    public function testResultName()
    {
        $propertyMetadata = new MetaPropertyMetadata();

        $this->assertNull($propertyMetadata->getResultName());
        $propertyMetadata->setName('name');
        $this->assertEquals('name', $propertyMetadata->getResultName());
        $propertyMetadata->setResultName('resultName');
        $this->assertEquals('resultName', $propertyMetadata->getResultName());
        $propertyMetadata->setResultName(null);
        $this->assertEquals('name', $propertyMetadata->getResultName());
    }
}
