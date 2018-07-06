<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\NestedObjectMetadataHelper;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectNestedObjectMetadataFactory;

class ObjectNestedObjectMetadataFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|NestedObjectMetadataHelper */
    private $nestedObjectMetadataHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectMetadataFactory */
    private $objectMetadataFactory;

    /** @var ObjectNestedObjectMetadataFactory */
    private $objectNestedObjectMetadataFactory;

    protected function setUp()
    {
        $this->nestedObjectMetadataHelper = $this->createMock(NestedObjectMetadataHelper::class);
        $this->objectMetadataFactory = $this->createMock(ObjectMetadataFactory::class);

        $this->objectNestedObjectMetadataFactory = new ObjectNestedObjectMetadataFactory(
            $this->nestedObjectMetadataHelper,
            $this->objectMetadataFactory
        );
    }

    public function testCreateAndAddNestedObjectMetadataForExcludedTargetField()
    {
        $entityMetadata = new EntityMetadata();
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

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
                self::identicalTo($config),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);
        $this->nestedObjectMetadataHelper->expects(self::never())
            ->method('getLinkedField');
        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddFieldMetadata');
        $this->nestedObjectMetadataHelper->expects(self::never())
            ->method('setTargetPropertyPath');

        $result = $this->objectNestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
            $entityMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testCreateAndAddNestedObjectMetadataForExcludedTargetFieldWhenExcludedPropertiesShouldNotBeIgnored()
    {
        $entityMetadata = new EntityMetadata();
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = true;
        $targetAction = 'testAction';

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
                self::identicalTo($config),
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
        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                $entityClass,
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

        $result = $this->objectNestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
            $entityMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testCreateAndAddNestedObjectMetadataForField()
    {
        $entityMetadata = new EntityMetadata();
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

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
                self::identicalTo($config),
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
        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                $entityClass,
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

        $result = $this->objectNestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
            $entityMetadata,
            $config,
            $entityClass,
            $fieldName,
            $field,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($associationMetadata, $result);
    }

    public function testCreateAndAddNestedObjectMetadataForMetaProperty()
    {
        $entityMetadata = new EntityMetadata();
        $config = new EntityDefinitionConfig();
        $entityClass = 'Test\Class';
        $fieldName = 'testField';
        $field = new EntityDefinitionFieldConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

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
                self::identicalTo($config),
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
        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddMetaPropertyMetadata')
            ->with(
                self::identicalTo($associationTargetMetadata),
                $entityClass,
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

        $result = $this->objectNestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
            $entityMetadata,
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
