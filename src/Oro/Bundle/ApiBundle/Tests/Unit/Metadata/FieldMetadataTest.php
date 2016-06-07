<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getName());
        $fieldMetadata->setName('fieldName');
        $this->assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testGetDataType()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getDataType());
        $fieldMetadata->setDataType('fieldType');
        $this->assertEquals('fieldType', $fieldMetadata->getDataType());
    }

    public function testIsNullable()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertFalse($fieldMetadata->isNullable());
        $fieldMetadata->setIsNullable(true);
        $this->assertTrue($fieldMetadata->isNullable());
    }

    public function testGetMaxLength()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getMaxLength());
        $fieldMetadata->setMaxLength(123);
        $this->assertEquals(123, $fieldMetadata->getMaxLength());
    }
}
