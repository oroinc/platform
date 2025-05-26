<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FieldMetadataTest extends TestCase
{
    public function testClone(): void
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

    public function testToArray(): void
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

    public function testToArrayWithRequiredPropertiesOnly(): void
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

    public function testToArrayInputOnlyField(): void
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

    public function testToArrayOutputOnlyField(): void
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

    public function testToArrayHiddenField(): void
    {
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('testName');
        $fieldMetadata->setHidden();

        self::assertEquals(
            [
                'name'   => 'testName',
                'hidden' => true
            ],
            $fieldMetadata->toArray()
        );
    }

    public function testNameInConstructor(): void
    {
        $fieldMetadata = new FieldMetadata('fieldName');
        self::assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testName(): void
    {
        $fieldMetadata = new FieldMetadata();

        self::assertNull($fieldMetadata->getName());
        $fieldMetadata->setName('fieldName');
        self::assertEquals('fieldName', $fieldMetadata->getName());
    }

    public function testPropertyPath(): void
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

    public function testDataType(): void
    {
        $fieldMetadata = new FieldMetadata();

        self::assertNull($fieldMetadata->getDataType());
        $fieldMetadata->setDataType('fieldType');
        self::assertEquals('fieldType', $fieldMetadata->getDataType());
    }

    public function testDirection(): void
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

    public function testHidden(): void
    {
        $fieldMetadata = new FieldMetadata();

        self::assertFalse($fieldMetadata->isHidden());
        self::assertTrue($fieldMetadata->isInput());
        self::assertTrue($fieldMetadata->isOutput());
        $fieldMetadata->setHidden();
        self::assertTrue($fieldMetadata->isHidden());
        self::assertFalse($fieldMetadata->isInput());
        self::assertFalse($fieldMetadata->isOutput());
    }

    public function testNullable(): void
    {
        $fieldMetadata = new FieldMetadata();

        self::assertFalse($fieldMetadata->isNullable());
        $fieldMetadata->setIsNullable(true);
        self::assertTrue($fieldMetadata->isNullable());
    }

    public function testMaxLength(): void
    {
        $fieldMetadata = new FieldMetadata();

        self::assertNull($fieldMetadata->getMaxLength());
        $fieldMetadata->setMaxLength(123);
        self::assertEquals(123, $fieldMetadata->getMaxLength());
    }
}
