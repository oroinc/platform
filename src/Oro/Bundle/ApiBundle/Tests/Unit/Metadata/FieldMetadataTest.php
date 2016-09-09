<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testClone()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');
        $fieldMetadata->setDataType('testDataType');
        $fieldMetadata->setIsNullable(true);
        $fieldMetadata->setMaxLength(123);

        $fieldMetadataClone = clone $fieldMetadata;

        $this->assertEquals($fieldMetadata, $fieldMetadataClone);
    }

    public function testToArray()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');
        $fieldMetadata->setDataType('testDataType');
        $fieldMetadata->setIsNullable(true);
        $fieldMetadata->setMaxLength(123);

        $this->assertEquals(
            [
                'name'       => 'testName',
                'data_type'  => 'testDataType',
                'nullable'   => true,
                'max_length' => 123,
            ],
            $fieldMetadata->toArray()
        );
    }

    public function testToArrayWithRequiredPropertiesOnly()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');

        $this->assertEquals(
            [
                'name' => 'testName'
            ],
            $fieldMetadata->toArray()
        );
    }

    public function testNameInConstructor()
    {
        $fieldMetadata = new FieldMetadata('fieldName');
        $this->assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testName()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getName());
        $fieldMetadata->setName('fieldName');
        $this->assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testDataType()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getDataType());
        $fieldMetadata->setDataType('fieldType');
        $this->assertEquals('fieldType', $fieldMetadata->getDataType());
    }

    public function testNullable()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertFalse($fieldMetadata->isNullable());
        $fieldMetadata->setIsNullable(true);
        $this->assertTrue($fieldMetadata->isNullable());
    }

    public function testMaxLength()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getMaxLength());
        $fieldMetadata->setMaxLength(123);
        $this->assertEquals(123, $fieldMetadata->getMaxLength());
    }
}
