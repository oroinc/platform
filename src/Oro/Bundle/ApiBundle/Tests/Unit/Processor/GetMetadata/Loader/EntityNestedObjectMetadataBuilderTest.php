<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataBuilder;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityNestedObjectMetadataBuilder;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\NestedObjectMetadataHelper;

class EntityNestedObjectMetadataBuilderTest extends LoaderTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nestedObjectMetadataHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityMetadataBuilder;

    /** @var EntityNestedObjectMetadataBuilder */
    protected $entityNestedObjectMetadataBuilder;

    protected function setUp()
    {
        $this->nestedObjectMetadataHelper = $this->getMockBuilder(NestedObjectMetadataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMetadataBuilder = $this->getMockBuilder(EntityMetadataBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityNestedObjectMetadataBuilder = new EntityNestedObjectMetadataBuilder(
            $this->nestedObjectMetadataHelper,
            $this->entityMetadataBuilder
        );
    }

    public function testAddNestedObjectMetadataForExcludedTargetField()
    {
        $entityMetadata = new EntityMetadata();
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $classMetadata = $this->getClassMetadataMock();

        $targetConfig = $field->createAndSetTargetEntity();
        $targetFieldName = 'targetField';
        $targetField = $targetConfig->addField($targetFieldName);
        $targetField->setExcluded();

        $associationMetadata = new AssociationMetadata();
        $associationTargetMetadata = new EntityMetadata();
        $associationMetadata->setTargetMetadata($associationTargetMetadata);

        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('addNestedObjectAssociation')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::never())
            ->method('getLinkedField');
        $this->entityMetadataBuilder->expects(self::never())
            ->method('addEntityFieldMetadata');
        $this->nestedObjectMetadataHelper->expects(self::never())
            ->method('setTargetPropertyPath');

        $result = $this->entityNestedObjectMetadataBuilder->addNestedObjectMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testAddNestedObjectMetadataForExcludedTargetFieldWhenExcludedPropertiesShouldNotBeIgnored()
    {
        $entityMetadata = new EntityMetadata();
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = true;
        $targetAction = 'testAction';

        $classMetadata = $this->getClassMetadataMock();

        $targetConfig = $field->createAndSetTargetEntity();
        $targetFieldName = 'targetField';
        $targetField = $targetConfig->addField($targetFieldName);
        $targetField->setExcluded();

        $linkedField = new EntityDefinitionFieldConfig();

        $associationMetadata = new AssociationMetadata();
        $associationTargetMetadata = new EntityMetadata();
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $targetPropertyMetadata = new FieldMetadata();

        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('addNestedObjectAssociation')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('getLinkedField')
            ->with(
                self::identicalTo($config),
                $entityClass,
                $fieldName,
                $targetFieldName,
                self::identicalTo($targetField)
            )
            ->willReturn($linkedField);
        $this->entityMetadataBuilder->expects(self::once())
            ->method('addEntityFieldMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                self::identicalTo($classMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            )
            ->willReturn($targetPropertyMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('setTargetPropertyPath')
            ->with(
                self::identicalTo($targetPropertyMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            );

        $result = $this->entityNestedObjectMetadataBuilder->addNestedObjectMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testAddNestedObjectMetadataForField()
    {
        $entityMetadata = new EntityMetadata();
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $classMetadata = $this->getClassMetadataMock();

        $targetConfig = $field->createAndSetTargetEntity();
        $targetFieldName = 'targetField';
        $targetField = $targetConfig->addField($targetFieldName);

        $linkedField = new EntityDefinitionFieldConfig();

        $associationMetadata = new AssociationMetadata();
        $associationTargetMetadata = new EntityMetadata();
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $targetPropertyMetadata = new FieldMetadata();

        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('addNestedObjectAssociation')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('getLinkedField')
            ->with(
                self::identicalTo($config),
                $entityClass,
                $fieldName,
                $targetFieldName,
                self::identicalTo($targetField)
            )
            ->willReturn($linkedField);
        $this->entityMetadataBuilder->expects(self::once())
            ->method('addEntityFieldMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                self::identicalTo($classMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            )
            ->willReturn($targetPropertyMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('setTargetPropertyPath')
            ->with(
                self::identicalTo($targetPropertyMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            );

        $result = $this->entityNestedObjectMetadataBuilder->addNestedObjectMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testAddNestedObjectMetadataForMetaProperty()
    {
        $entityMetadata = new EntityMetadata();
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $classMetadata = $this->getClassMetadataMock();

        $targetConfig = $field->createAndSetTargetEntity();
        $targetFieldName = 'targetField';
        $targetField = $targetConfig->addField($targetFieldName);

        $linkedField = new EntityDefinitionFieldConfig();
        $linkedField->setMetaProperty(true);

        $associationMetadata = new AssociationMetadata();
        $associationTargetMetadata = new EntityMetadata();
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $targetPropertyMetadata = new MetaPropertyMetadata();

        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('addNestedObjectAssociation')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('getLinkedField')
            ->with(
                self::identicalTo($config),
                $entityClass,
                $fieldName,
                $targetFieldName,
                self::identicalTo($targetField)
            )
            ->willReturn($linkedField);
        $this->entityMetadataBuilder->expects(self::once())
            ->method('addEntityMetaPropertyMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                self::identicalTo($classMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            )
            ->willReturn($targetPropertyMetadata);
        $this->nestedObjectMetadataHelper->expects(self::once())
            ->method('setTargetPropertyPath')
            ->with(
                self::identicalTo($targetPropertyMetadata),
                $targetFieldName,
                self::identicalTo($targetField),
                $targetAction
            );

        $result = $this->entityNestedObjectMetadataBuilder->addNestedObjectMetadata(
            $entityMetadata,
            $classMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }
}
