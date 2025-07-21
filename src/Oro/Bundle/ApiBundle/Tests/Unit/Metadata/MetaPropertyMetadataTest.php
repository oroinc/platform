<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MetaPropertyMetadataTest extends TestCase
{
    public function testClone(): void
    {
        $propertyMetadata = new MetaPropertyMetadata('testName', 'testDataType', 'testPropertyPath');

        $propertyMetadataClone = clone $propertyMetadata;

        self::assertEquals($propertyMetadata, $propertyMetadataClone);
    }

    public function testToArray(): void
    {
        $propertyMetadata = new MetaPropertyMetadata('testName', 'testDataType', 'testPropertyPath');

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
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');

        self::assertEquals(
            [
                'name' => 'testName'
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testToArrayInputOnlyProperty(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');
        $propertyMetadata->setDirection(true, false);

        self::assertEquals(
            [
                'name'      => 'testName',
                'direction' => 'input-only'
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testToArrayOutputOnlyProperty(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');
        $propertyMetadata->setDirection(false, true);

        self::assertEquals(
            [
                'name'      => 'testName',
                'direction' => 'output-only'
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testToArrayHiddenProperty(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();
        $propertyMetadata->setName('testName');
        $propertyMetadata->setHidden();

        self::assertEquals(
            [
                'name'   => 'testName',
                'hidden' => true
            ],
            $propertyMetadata->toArray()
        );
    }

    public function testNameInConstructor(): void
    {
        $propertyMetadata = new MetaPropertyMetadata('name');
        self::assertEquals('name', $propertyMetadata->getName());
    }

    public function testNameAndDataTypeInConstructor(): void
    {
        $propertyMetadata = new MetaPropertyMetadata('name', 'string');
        self::assertEquals('name', $propertyMetadata->getName());
        self::assertEquals('string', $propertyMetadata->getDataType());
    }

    public function testNameAndDataTypeAndPropertyPathInConstructor(): void
    {
        $propertyMetadata = new MetaPropertyMetadata('name', 'string', 'property');
        self::assertEquals('name', $propertyMetadata->getName());
        self::assertEquals('string', $propertyMetadata->getDataType());
        self::assertEquals('property', $propertyMetadata->getPropertyPath());
    }

    public function testName(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertNull($propertyMetadata->getName());
        $propertyMetadata->setName('name');
        self::assertEquals('name', $propertyMetadata->getName());
    }

    public function testPropertyPath(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertNull($propertyMetadata->getPropertyPath());
        $propertyMetadata->setName('name');
        self::assertEquals('name', $propertyMetadata->getPropertyPath());
        $propertyMetadata->setPropertyPath('propertyPath');
        self::assertEquals('propertyPath', $propertyMetadata->getPropertyPath());
        $propertyMetadata->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
        self::assertNull($propertyMetadata->getPropertyPath());
        $propertyMetadata->setPropertyPath(null);
        self::assertEquals('name', $propertyMetadata->getPropertyPath());
    }

    public function testDataType(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertNull($propertyMetadata->getDataType());
        $propertyMetadata->setDataType('dataType');
        self::assertEquals('dataType', $propertyMetadata->getDataType());
    }

    public function testDirection(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertTrue($propertyMetadata->isInput());
        self::assertTrue($propertyMetadata->isOutput());
        $propertyMetadata->setDirection(true, false);
        self::assertTrue($propertyMetadata->isInput());
        self::assertFalse($propertyMetadata->isOutput());
        $propertyMetadata->setDirection(false, true);
        self::assertFalse($propertyMetadata->isInput());
        self::assertTrue($propertyMetadata->isOutput());
        $propertyMetadata->setDirection(true, false);
        self::assertTrue($propertyMetadata->isInput());
        self::assertFalse($propertyMetadata->isOutput());
        $propertyMetadata->setDirection(false, false);
        self::assertFalse($propertyMetadata->isInput());
        self::assertFalse($propertyMetadata->isOutput());
        $propertyMetadata->setDirection(true, true);
        self::assertTrue($propertyMetadata->isInput());
        self::assertTrue($propertyMetadata->isOutput());
    }

    public function testHidden(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertFalse($propertyMetadata->isHidden());
        self::assertTrue($propertyMetadata->isInput());
        self::assertTrue($propertyMetadata->isOutput());
        $propertyMetadata->setHidden();
        self::assertTrue($propertyMetadata->isHidden());
        self::assertFalse($propertyMetadata->isInput());
        self::assertFalse($propertyMetadata->isOutput());
    }

    public function testResultName(): void
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertNull($propertyMetadata->getResultName());
        $propertyMetadata->setName('name');
        self::assertEquals('name', $propertyMetadata->getResultName());
        $propertyMetadata->setResultName('resultName');
        self::assertEquals('resultName', $propertyMetadata->getResultName());
        $propertyMetadata->setResultName(null);
        self::assertEquals('name', $propertyMetadata->getResultName());
    }

    public function testAssociationLevel()
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertFalse($propertyMetadata->isAssociationLevel());
        $propertyMetadata->setAssociationLevel(true);
        self::assertTrue($propertyMetadata->isAssociationLevel());
        $propertyMetadata->setAssociationLevel(false);
        self::assertFalse($propertyMetadata->isAssociationLevel());
    }
}
