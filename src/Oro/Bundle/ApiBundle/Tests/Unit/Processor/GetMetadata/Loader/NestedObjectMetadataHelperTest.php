<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\NestedObjectMetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;

class NestedObjectMetadataHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataHelper */
    private $metadataHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectMetadataFactory */
    private $objectMetadataFactory;

    /** @var NestedObjectMetadataHelper */
    private $nestedObjectMetadataHelper;

    protected function setUp()
    {
        $this->metadataHelper = $this->createMock(MetadataHelper::class);
        $this->objectMetadataFactory = $this->createMock(ObjectMetadataFactory::class);

        $this->nestedObjectMetadataHelper = new NestedObjectMetadataHelper(
            $this->metadataHelper,
            $this->objectMetadataFactory
        );
    }

    public function testAddNestedObjectAssociation()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $targetClass = 'Test\TargetClass';
        $field->setFormOptions(['data_class' => $targetClass]);
        $targetEntityMetadata = new EntityMetadata();

        $associationMetadata = new AssociationMetadata();

        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                self::identicalTo($config),
                $fieldName,
                self::identicalTo($field),
                $targetAction,
                $targetClass
            )
            ->willReturn($associationMetadata);
        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($targetClass, self::identicalTo($targetConfig))
            ->willReturn($targetEntityMetadata);

        $result = $this->nestedObjectMetadataHelper->addNestedObjectAssociation(
            $entityMetadata,
            $entityClass,
            $config,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
        self::assertSame($targetEntityMetadata, $result->getTargetMetadata());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "data_class" form option should be specified for the nested object. Field: Test\Class::testField.
     */
    // @codingStandardsIgnoreEnd
    public function testAddNestedObjectAssociationShouldThrowExceptionWhenNoDataClassFormOption()
    {
        $config = new EntityDefinitionConfig();

        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddAssociationMetadata');
        $this->objectMetadataFactory->expects(self::never())
            ->method('createObjectMetadata');

        $this->nestedObjectMetadataHelper->addNestedObjectAssociation(
            $entityMetadata,
            $entityClass,
            $config,
            $fieldName,
            $field,
            $targetAction
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "association.field1" property path is not supported for the nested object. Parent Field: Test\ParentClass::parentField. Target Field: targetField.
     */
    // @codingStandardsIgnoreEnd
    public function testGetLinkedFieldWhenTargetFieldIsLinkedToAssociationField()
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentClassName = 'Test\ParentClass';
        $parentFieldName = 'parentField';
        $targetFieldName = 'targetField';
        $targetField = new EntityDefinitionFieldConfig();

        $parentConfig->addField('association')
            ->createAndSetTargetEntity()
            ->addField('field1');
        $targetField->setPropertyPath('association.field1');

        $this->nestedObjectMetadataHelper->getLinkedField(
            $parentConfig,
            $parentClassName,
            $parentFieldName,
            $targetFieldName,
            $targetField
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage An association is not supported for the nested object. Parent Field: Test\ParentClass::parentField. Target Field: targetField.
     */
    // @codingStandardsIgnoreEnd
    public function testGetLinkedFieldWhenTargetFieldIsLinkedToAssociation()
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentClassName = 'Test\ParentClass';
        $parentFieldName = 'parentField';
        $targetFieldName = 'targetField';
        $targetField = new EntityDefinitionFieldConfig();

        $parentConfig->addField('association')
            ->createAndSetTargetEntity();
        $targetField->setPropertyPath('association');

        $this->nestedObjectMetadataHelper->getLinkedField(
            $parentConfig,
            $parentClassName,
            $parentFieldName,
            $targetFieldName,
            $targetField
        );
    }

    public function testGetLinkedFieldWhenTargetFieldIsLinkedToField()
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentClassName = 'Test\ParentClass';
        $parentFieldName = 'parentField';
        $targetFieldName = 'targetField';
        $targetField = new EntityDefinitionFieldConfig();

        $linkedField = $parentConfig->addField('field');
        $targetField->setPropertyPath('field');

        $result = $this->nestedObjectMetadataHelper->getLinkedField(
            $parentConfig,
            $parentClassName,
            $parentFieldName,
            $targetFieldName,
            $targetField
        );
        self::assertSame($linkedField, $result);
    }

    public function testSetTargetPropertyPathWhenFormPropertyPathEqualsToFieldName()
    {
        $fieldName = 'testField';
        $propertyMetadata = new FieldMetadata($fieldName);
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $this->metadataHelper->expects(self::once())
            ->method('getFormPropertyPath')
            ->with(self::identicalTo($field), $targetAction)
            ->willReturn($fieldName);

        $this->nestedObjectMetadataHelper->setTargetPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertEquals($fieldName, $propertyMetadata->getPropertyPath());
    }

    public function testSetTargetPropertyPathWhenFormPropertyPathIsNotEqualToFieldName()
    {
        $fieldName = 'testField';
        $propertyMetadata = new FieldMetadata($fieldName);
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';
        $formPropertyPath = 'formPropertyPath';

        $this->metadataHelper->expects(self::once())
            ->method('getFormPropertyPath')
            ->with(self::identicalTo($field), $targetAction)
            ->willReturn($formPropertyPath);

        $this->nestedObjectMetadataHelper->setTargetPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertEquals($formPropertyPath, $propertyMetadata->getPropertyPath());
    }
}
