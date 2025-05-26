<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\MetaAttributeMetadata;
use PHPUnit\Framework\TestCase;

class MetaAttributeMetadataTest extends TestCase
{
    public function testClone(): void
    {
        $propertyMetadata = new MetaAttributeMetadata('testName', 'testDataType', 'testPropertyPath');

        $propertyMetadataClone = clone $propertyMetadata;

        self::assertEquals($propertyMetadata, $propertyMetadataClone);
    }

    public function testToArray(): void
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

    public function testToArrayWithRequiredPropertiesOnly(): void
    {
        $propertyMetadata = new MetaAttributeMetadata('testName');

        self::assertEquals(
            [
                'name' => 'testName'
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testConstructorWithParameters(): void
    {
        $propertyMetadata = new MetaAttributeMetadata('name', 'dataType', 'propertyPath');
        self::assertEquals('name', $propertyMetadata->getName());
        self::assertEquals('dataType', $propertyMetadata->getDataType());
        self::assertEquals('propertyPath', $propertyMetadata->getPropertyPath());
    }
}
