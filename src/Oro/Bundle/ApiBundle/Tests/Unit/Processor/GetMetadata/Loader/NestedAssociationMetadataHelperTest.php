<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\NestedAssociationMetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

class NestedAssociationMetadataHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataHelper */
    private $metadataHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectMetadataFactory */
    private $objectMetadataFactory;

    /** @var NestedAssociationMetadataHelper */
    private $nestedAssociationMetadataHelper;

    protected function setUp()
    {
        $this->metadataHelper = $this->createMock(MetadataHelper::class);
        $this->objectMetadataFactory = $this->createMock(ObjectMetadataFactory::class);

        $this->nestedAssociationMetadataHelper = new NestedAssociationMetadataHelper(
            $this->metadataHelper,
            $this->objectMetadataFactory
        );
    }

    public function testAddNestedAssociation()
    {
        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $classField = $targetConfig->addField('__class__');
        $classField->setDataType('string');
        $classField->setPropertyPath('entityClass');
        $idField = $targetConfig->addField('id');
        $idField->setDataType('integer');
        $idField->setPropertyPath('entityId');

        $targetClass = 'Test\TargetClass';
        $field->setTargetClass($targetClass);

        $targetEntityMetadata = new EntityMetadata();

        $this->metadataHelper->expects(self::once())
            ->method('setPropertyPath')
            ->with(
                self::isInstanceOf(AssociationMetadata::class),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );
        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($targetClass, self::identicalTo($targetConfig))
            ->willReturn($targetEntityMetadata);

        $expectedAssociationMetadata = new AssociationMetadata($fieldName);
        $expectedAssociationMetadata->setDataType($idField->getDataType());
        $expectedAssociationMetadata->setIsNullable(true);
        $expectedAssociationMetadata->setCollapsed(true);
        $expectedAssociationMetadata->setAssociationType(RelationType::MANY_TO_ONE);
        $expectedAssociationMetadata->setTargetClassName($targetClass);
        $expectedAssociationMetadata->setTargetMetadata($targetEntityMetadata);

        $result = $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertTrue($entityMetadata->hasAssociation($fieldName));
        self::assertEquals($expectedAssociationMetadata, $result);
        self::assertTrue($targetEntityMetadata->isInheritedType());
    }

    public function testAddNestedAssociationWhenDataTypeIsNotSetForIdField()
    {
        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $classField = $targetConfig->addField('__class__');
        $classField->setDataType('string');
        $classField->setPropertyPath('entityClass');
        $idField = $targetConfig->addField('id');
        $idField->setPropertyPath('entityId');

        $targetClass = 'Test\TargetClass';
        $field->setTargetClass($targetClass);

        $targetEntityMetadata = new EntityMetadata();

        $this->metadataHelper->expects(self::once())
            ->method('setPropertyPath')
            ->with(
                self::isInstanceOf(AssociationMetadata::class),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );
        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($targetClass, self::identicalTo($targetConfig))
            ->willReturn($targetEntityMetadata);

        $expectedAssociationMetadata = new AssociationMetadata($fieldName);
        $expectedAssociationMetadata->setIsNullable(true);
        $expectedAssociationMetadata->setCollapsed(true);
        $expectedAssociationMetadata->setAssociationType(RelationType::MANY_TO_ONE);
        $expectedAssociationMetadata->setTargetClassName($targetClass);
        $expectedAssociationMetadata->setTargetMetadata($targetEntityMetadata);

        $result = $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertTrue($entityMetadata->hasAssociation($fieldName));
        self::assertEquals($expectedAssociationMetadata, $result);
        self::assertTrue($targetEntityMetadata->isInheritedType());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "__class__" field should be configured for the nested association. Parent Field: Test\Class::testField.
     */
    // @codingStandardsIgnoreEnd
    public function testAddNestedAssociationWhenClassFieldDoesNotExist()
    {
        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $field->createAndSetTargetEntity();

        $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage A property path should be configured for the "__class__" field. Parent Field: Test\Class::testField.
     */
    // @codingStandardsIgnoreEnd
    public function testAddNestedAssociationWhenClassFieldDoesNotHavePropertyPath()
    {
        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $targetConfig->addField('__class__');

        $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "__class__" field should not be an association. Parent Field: Test\Class::testField.
     */
    // @codingStandardsIgnoreEnd
    public function testAddNestedAssociationWhenClassFieldIsAssociation()
    {
        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $classField = $targetConfig->addField('__class__');
        $classField->setPropertyPath('entityClass');
        $classField->createAndSetTargetEntity();

        $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "id" field should be configured for the nested association. Parent Field: Test\Class::testField.
     */
    // @codingStandardsIgnoreEnd
    public function testAddNestedAssociationWhenIdFieldDoesNotExist()
    {
        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $classField = $targetConfig->addField('__class__');
        $classField->setPropertyPath('entityClass');

        $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage A property path should be configured for the "id" field. Parent Field: Test\Class::testField.
     */
    // @codingStandardsIgnoreEnd
    public function testAddNestedAssociationWhenIdFieldDoesNotHavePropertyPath()
    {
        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $classField = $targetConfig->addField('__class__');
        $classField->setPropertyPath('entityClass');
        $targetConfig->addField('id');

        $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage The "id" field should not be an association. Parent Field: Test\Class::testField.
     */
    public function testAddNestedAssociationWhenIdFieldIsAssociation()
    {
        $entityMetadata = new EntityMetadata();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $targetAction = 'testAction';

        $targetConfig = $field->createAndSetTargetEntity();
        $classField = $targetConfig->addField('__class__');
        $classField->setPropertyPath('entityClass');
        $idField = $targetConfig->addField('id');
        $idField->setPropertyPath('entityId');
        $idField->createAndSetTargetEntity();

        $this->nestedAssociationMetadataHelper->addNestedAssociation(
            $entityMetadata,
            $entityClass,
            $fieldName,
            $field,
            $targetAction
        );
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

        $this->nestedAssociationMetadataHelper->setTargetPropertyPath(
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

        $this->nestedAssociationMetadataHelper->setTargetPropertyPath(
            $propertyMetadata,
            $fieldName,
            $field,
            $targetAction
        );
        self::assertEquals($formPropertyPath, $propertyMetadata->getPropertyPath());
    }

    public function testGetIdentifierFieldName()
    {
        self::assertEquals('id', $this->nestedAssociationMetadataHelper->getIdentifierFieldName());
    }
}
