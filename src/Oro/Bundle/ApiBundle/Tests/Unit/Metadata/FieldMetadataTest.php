<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FieldMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testClone()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');
        $fieldMetadata->setPropertyPath('testPropertyPath');
        $fieldMetadata->setDataType('testDataType');
        $fieldMetadata->setIsNullable(true);
        $fieldMetadata->setMaxLength(123);

        $fieldMetadataClone = clone $fieldMetadata;

        self::assertEquals($fieldMetadata, $fieldMetadataClone);
    }

    public function testToArray()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');
        $fieldMetadata->setPropertyPath('testPropertyPath');
        $fieldMetadata->setDataType('testDataType');
        $fieldMetadata->setIsNullable(true);
        $fieldMetadata->setMaxLength(123);

        self::assertEquals(
            [
                'name'          => 'testName',
                'property_path' => 'testPropertyPath',
                'data_type'     => 'testDataType',
                'nullable'      => true,
                'max_length'    => 123
            ],
            $fieldMetadata->toArray()
        );
    }

    public function testToArrayWithRequiredPropertiesOnly()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');

        self::assertEquals(
            [
                'name' => 'testName'
            ],
            $fieldMetadata->toArray()
        );
    }

    public function testToArrayInputOnlyField()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');
        $fieldMetadata->setDirection(true, false);

        self::assertEquals(
            [
                'name'      => 'testName',
                'direction' => 'input-only'
            ],
            $fieldMetadata->toArray()
        );
    }

    public function testToArrayOutputOnlyField()
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');
        $fieldMetadata->setDirection(false, true);

        self::assertEquals(
            [
                'name'      => 'testName',
                'direction' => 'output-only'
            ],
            $fieldMetadata->toArray()
        );
    }

    public function testNameInConstructor()
    {
        $fieldMetadata = new FieldMetadata('fieldName');
        self::assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testName()
    {
        $fieldMetadata = new FieldMetadata();

        self::assertNull($fieldMetadata->getName());
        $fieldMetadata->setName('fieldName');
        self::assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testPropertyPath()
    {
        $fieldMetadata = new FieldMetadata();

        self::assertNull($fieldMetadata->getPropertyPath());
        $fieldMetadata->setName('name');
        self::assertEquals('name', $fieldMetadata->getPropertyPath());
        $fieldMetadata->setPropertyPath('propertyPath');
        self::assertEquals('propertyPath', $fieldMetadata->getPropertyPath());
        $fieldMetadata->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        self::assertNull($fieldMetadata->getPropertyPath());
        $fieldMetadata->setPropertyPath(null);
        self::assertEquals('name', $fieldMetadata->getPropertyPath());
    }

    public function testDataType()
    {
        $fieldMetadata = new FieldMetadata();

        self::assertNull($fieldMetadata->getDataType());
        $fieldMetadata->setDataType('fieldType');
        self::assertEquals('fieldType', $fieldMetadata->getDataType());
    }

    public function testDirection()
    {
        $fieldMetadata = new FieldMetadata();

        self::assertTrue($fieldMetadata->isInput());
        self::assertTrue($fieldMetadata->isOutput());
        $fieldMetadata->setDirection(true, false);
        self::assertTrue($fieldMetadata->isInput());
        self::assertFalse($fieldMetadata->isOutput());
        $fieldMetadata->setDirection(false, true);
        self::assertFalse($fieldMetadata->isInput());
        self::assertTrue($fieldMetadata->isOutput());
        $fieldMetadata->setDirection(true, false);
        self::assertTrue($fieldMetadata->isInput());
        self::assertFalse($fieldMetadata->isOutput());
        $fieldMetadata->setDirection(false, false);
        self::assertFalse($fieldMetadata->isInput());
        self::assertFalse($fieldMetadata->isOutput());
        $fieldMetadata->setDirection(true, true);
        self::assertTrue($fieldMetadata->isInput());
        self::assertTrue($fieldMetadata->isOutput());
    }

    public function testNullable()
    {
        $fieldMetadata = new FieldMetadata();

        self::assertFalse($fieldMetadata->isNullable());
        $fieldMetadata->setIsNullable(true);
        self::assertTrue($fieldMetadata->isNullable());
    }

    public function testMaxLength()
    {
        $fieldMetadata = new FieldMetadata();

        self::assertNull($fieldMetadata->getMaxLength());
        $fieldMetadata->setMaxLength(123);
        self::assertEquals(123, $fieldMetadata->getMaxLength());
    }
}
