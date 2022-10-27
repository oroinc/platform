<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;

class MetaAttributeMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testClone()
    {
        $propertyMetadata = new MetaAttributeMetadata('testName', 'testDataType', 'testPropertyPath');

        $propertyMetadataClone = clone $propertyMetadata;

        self::assertEquals($propertyMetadata, $propertyMetadataClone);
    }

    public function testToArray()
    {
        $propertyMetadata = new MetaAttributeMetadata('testName', 'testDataType', 'testPropertyPath');

        self::assertEquals(
            [
                'name'          => 'testName',
                'property_path' => 'testPropertyPath',
                'data_type'     => 'testDataType'
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testToArrayWithRequiredPropertiesOnly()
    {
        $propertyMetadata = new MetaAttributeMetadata('testName');

        self::assertEquals(
            [
                'name' => 'testName'
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testConstructorWithParameters()
    {
        $propertyMetadata = new MetaAttributeMetadata('name', 'dataType', 'propertyPath');
        self::assertEquals('name', $propertyMetadata->getName());
        self::assertEquals('dataType', $propertyMetadata->getDataType());
        self::assertEquals('propertyPath', $propertyMetadata->getPropertyPath());
    }
}
