<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Request\ApiActions;

class MetadataHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var MetadataHelper */
    private $metadataHelper;

    protected function setUp()
    {
        $this->metadataHelper = new MetadataHelper();
    }

    public function testAssertDataTypeForNotEmptyDataType()
    {
        $dataType = 'string';
        self::assertEquals(
            $dataType,
            $this->metadataHelper->assertDataType($dataType, 'Test\Class', 'testField')
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "data_type" configuration attribute should be specified for the "testField" field of the "Test\Class" entity.
     */
    // @codingStandardsIgnoreEnd
    public function testAssertDataTypeForEmptyDataType()
    {
        $this->metadataHelper->assertDataType('', 'Test\Class', 'testField');
    }

    /**
     * @dataProvider getFormPropertyPathProvider
     */
    public function testGetFormPropertyPath($expectedPropertyPath, $formOptions, $targetAction)
    {
        $field = new EntityDefinitionFieldConfig();
        $field->setFormOptions($formOptions);

        self::assertSame(
            $expectedPropertyPath,
            $this->metadataHelper->getFormPropertyPath($field, $targetAction)
        );
    }

    public function getFormPropertyPathProvider()
    {
        return [
            [
                null,
                null,
                ApiActions::CREATE
            ],
            [
                null,
                null,
                ApiActions::UPDATE
            ],
            [
                null,
                ['data_class' => 'Test\Class'],
                ApiActions::CREATE
            ],
            [
                null,
                ['data_class' => 'Test\Class'],
                ApiActions::UPDATE
            ],
            [
                'test',
                ['property_path' => 'test'],
                ApiActions::CREATE
            ],
            [
                'test',
                ['property_path' => 'test'],
                ApiActions::UPDATE
            ],
            [
                null,
                ['property_path' => 'test'],
                ApiActions::GET
            ]
        ];
    }

    public function testShouldNotSetPropertyPathIfItEqualsToConfigPropertyPath()
    {
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $propertyMetadata = new FieldMetadata($fieldName);

        $this->metadataHelper->setPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            ApiActions::CREATE
        );
        self::assertEquals($fieldName, $propertyMetadata->getPropertyPath());
    }

    public function testShouldNotSetPropertyPathIfFormPropertyPathEqualsToConfigPropertyPath()
    {
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $field->setFormOptions(['property_path' => $fieldName]);
        $propertyMetadata = new FieldMetadata($fieldName);

        $this->metadataHelper->setPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            ApiActions::CREATE
        );
        self::assertEquals($fieldName, $propertyMetadata->getPropertyPath());
    }

    public function testShouldSetFormPropertyPathForCreateAction()
    {
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $field->setFormOptions(['property_path' => 'propertyPath']);
        $propertyMetadata = new FieldMetadata($fieldName);

        $this->metadataHelper->setPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            ApiActions::CREATE
        );
        self::assertEquals('propertyPath', $propertyMetadata->getPropertyPath());
    }

    public function testShouldSetFormPropertyPathForUpdateAction()
    {
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $field->setFormOptions(['property_path' => 'propertyPath']);
        $propertyMetadata = new FieldMetadata($fieldName);

        $this->metadataHelper->setPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            ApiActions::UPDATE
        );
        self::assertEquals('propertyPath', $propertyMetadata->getPropertyPath());
    }

    public function testShouldNotSetFormPropertyPathForGetAction()
    {
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $field->setFormOptions(['property_path' => 'propertyPath']);
        $propertyMetadata = new FieldMetadata($fieldName);

        $this->metadataHelper->setPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            ApiActions::GET
        );
        self::assertEquals($fieldName, $propertyMetadata->getPropertyPath());
    }
}
