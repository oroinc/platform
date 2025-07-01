<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Metadata;

use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MetaPropertyMetadataTest extends \PHPUnit\Framework\TestCase
{
    public function testClone()
    {
        $propertyMetadata = new MetaPropertyMetadata('testName', 'testDataType', 'testPropertyPath');

        $propertyMetadataClone = clone $propertyMetadata;

        self::assertEquals($propertyMetadata, $propertyMetadataClone);
    }

    public function testToArray()
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

    public function testToArrayWithRequiredPropertiesOnly()
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

    public function testToArrayInputOnlyProperty()
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

    public function testToArrayOutputOnlyProperty()
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

    public function testToArrayHiddenProperty()
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

    public function testNameInConstructor()
    {
        $propertyMetadata = new MetaPropertyMetadata('name');
        self::assertEquals('name', $propertyMetadata->getName());
    }

    public function testNameAndDataTypeInConstructor()
    {
        $propertyMetadata = new MetaPropertyMetadata('name', 'string');
        self::assertEquals('name', $propertyMetadata->getName());
        self::assertEquals('string', $propertyMetadata->getDataType());
    }

    public function testNameAndDataTypeAndPropertyPathInConstructor()
    {
        $propertyMetadata = new MetaPropertyMetadata('name', 'string', 'property');
        self::assertEquals('name', $propertyMetadata->getName());
        self::assertEquals('string', $propertyMetadata->getDataType());
        self::assertEquals('property', $propertyMetadata->getPropertyPath());
    }

    public function testName()
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertNull($propertyMetadata->getName());
        $propertyMetadata->setName('name');
        self::assertEquals('name', $propertyMetadata->getName());
    }

    public function testPropertyPath()
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

    public function testDataType()
    {
        $propertyMetadata = new MetaPropertyMetadata();

        self::assertNull($propertyMetadata->getDataType());
        $propertyMetadata->setDataType('dataType');
        self::assertEquals('dataType', $propertyMetadata->getDataType());
    }

    public function testDirection()
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

    public function testHidden()
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

    public function testResultName()
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
