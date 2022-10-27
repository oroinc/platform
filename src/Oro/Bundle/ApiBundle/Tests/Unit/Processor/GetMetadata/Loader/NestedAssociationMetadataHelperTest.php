<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\MetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\NestedAssociationMetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NestedAssociationMetadataHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataHelper */
    private $metadataHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectMetadataFactory */
    private $objectMetadataFactory;

    /** @var NestedAssociationMetadataHelper */
    private $nestedAssociationMetadataHelper;

    protected function setUp(): void
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
        $entityMetadata = new EntityMetadata('Test\Entity');
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

        $targetEntityMetadata = new EntityMetadata('Test\Entity');

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
        $entityMetadata = new EntityMetadata('Test\Entity');
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

        $targetEntityMetadata = new EntityMetadata('Test\Entity');

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

    public function testAddNestedAssociationWhenClassFieldDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "__class__" field should be configured for the nested association.'
            . ' Parent Field: Test\Class::testField.'
        );

        $entityMetadata = new EntityMetadata('Test\Entity');
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

    public function testAddNestedAssociationWhenClassFieldDoesNotHavePropertyPath()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'A property path should be configured for the "__class__" field. Parent Field: Test\Class::testField.'
        );

        $entityMetadata = new EntityMetadata('Test\Entity');
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

    public function testAddNestedAssociationWhenClassFieldIsAssociation()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "__class__" field should not be an association. Parent Field: Test\Class::testField.'
        );

        $entityMetadata = new EntityMetadata('Test\Entity');
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

    public function testAddNestedAssociationWhenIdFieldDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "id" field should be configured for the nested association. Parent Field: Test\Class::testField.'
        );

        $entityMetadata = new EntityMetadata('Test\Entity');
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

    public function testAddNestedAssociationWhenIdFieldDoesNotHavePropertyPath()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'A property path should be configured for the "id" field. Parent Field: Test\Class::testField.'
        );

        $entityMetadata = new EntityMetadata('Test\Entity');
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

    public function testAddNestedAssociationWhenIdFieldIsAssociation()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The "id" field should not be an association. Parent Field: Test\Class::testField.'
        );

        $entityMetadata = new EntityMetadata('Test\Entity');
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
