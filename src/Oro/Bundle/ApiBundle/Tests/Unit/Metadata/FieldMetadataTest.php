<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testClone()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('fieldName');
        $fieldMetadata->set('test_scalar', 'value');
        $objValue = new \stdClass();
        $objValue->someProp = 123;
        $fieldMetadata->set('test_object', $objValue);

        $fieldMetadataClone = clone $fieldMetadata;

        $this->assertEquals($fieldMetadata, $fieldMetadataClone);
        $this->assertNotSame($objValue, $fieldMetadataClone->get('test_object'));
    }

    public function testConstructor()
    {
        $fieldMetadata = new FieldMetadata('fieldName');
        $this->assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testGetName()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getName());
        $this->assertSame($fieldMetadata, $fieldMetadata->setName('fieldName'));
        $this->assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testGetDataType()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertNull($fieldMetadata->getDataType());
        $this->assertSame($fieldMetadata, $fieldMetadata->setDataType('fieldType'));
        $this->assertEquals('fieldType', $fieldMetadata->getDataType());
    }

    public function testIsNullable()
    {
        $fieldMetadata = new FieldMetadata();

        $this->assertFalse($fieldMetadata->isNullable());
        $this->assertSame($fieldMetadata, $fieldMetadata->setIsNullable(true));
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
