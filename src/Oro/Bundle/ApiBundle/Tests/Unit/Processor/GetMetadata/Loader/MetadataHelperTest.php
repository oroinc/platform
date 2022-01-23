<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Request\ApiAction;

class MetadataHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var MetadataHelper */
    private $metadataHelper;

    protected function setUp(): void
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

    /**
     * @dataProvider emptyDataTypeDataProvider
     */
    public function testAssertDataTypeForEmptyDataType(?string $dataType)
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "data_type" configuration attribute should be specified'
            . ' for the "testField" field of the "Test\Class" entity.'
        );

        $this->metadataHelper->assertDataType($dataType, 'Test\Class', 'testField');
    }

    public function emptyDataTypeDataProvider(): array
    {
        return [
            [''],
            [null]
        ];
    }

    /**
     * @dataProvider getFormPropertyPathProvider
     */
    public function testGetFormPropertyPath(?string $expectedPropertyPath, ?array $formOptions, string $targetAction)
    {
        $field = new EntityDefinitionFieldConfig();
        $field->setFormOptions($formOptions);

        self::assertSame(
            $expectedPropertyPath,
            $this->metadataHelper->getFormPropertyPath($field, $targetAction)
        );
    }

    public function getFormPropertyPathProvider(): array
    {
        return [
            [
                null,
                null,
                ApiAction::CREATE
            ],
            [
                null,
                null,
                ApiAction::UPDATE
            ],
            [
                null,
                ['data_class' => 'Test\Class'],
                ApiAction::CREATE
            ],
            [
                null,
                ['data_class' => 'Test\Class'],
                ApiAction::UPDATE
            ],
            [
                'test',
                ['property_path' => 'test'],
                ApiAction::CREATE
            ],
            [
                'test',
                ['property_path' => 'test'],
                ApiAction::UPDATE
            ],
            [
                null,
                ['property_path' => 'test'],
                ApiAction::GET
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
            ApiAction::CREATE
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
            ApiAction::CREATE
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
            ApiAction::CREATE
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
            ApiAction::UPDATE
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
            ApiAction::GET
        );
        self::assertEquals($fieldName, $propertyMetadata->getPropertyPath());
    }
}
