<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory as MetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityNestedObjectMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EntityMetadataLoaderTest extends LoaderTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $objectMetadataFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityMetadataFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nestedObjectMetadataFactory;

    /** @var EntityMetadataLoader */
    protected $entityMetadataLoader;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataFactory = $this->getMockBuilder(MetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectMetadataFactory = $this->getMockBuilder(ObjectMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityMetadataFactory = $this->getMockBuilder(EntityMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nestedObjectMetadataFactory = $this->getMockBuilder(EntityNestedObjectMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadataLoader = new EntityMetadataLoader(
            $this->doctrineHelper,
            $this->metadataFactory,
            $this->objectMetadataFactory,
            $this->entityMetadataFactory,
            $this->nestedObjectMetadataFactory
        );
    }

    public function testForIdentifierField()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $entityMetadata->setIdentifierFieldNames([$fieldName]);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$fieldName]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertEquals([$fieldName], $entityMetadata->getIdentifierFieldNames());
    }

    public function testForRenamedIdentifierField()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $propertyPath = 'testPropertyPath';
        $field = $config->addField($fieldName);
        $field->setPropertyPath($propertyPath);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $entityMetadata->setIdentifierFieldNames([$propertyPath]);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$propertyPath]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertEquals([$fieldName], $entityMetadata->getIdentifierFieldNames());
    }

    public function testForExcludedField()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setExcluded();

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$fieldName]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::never())
            ->method('createAndAddFieldMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForExcludedFieldWhenExcludedPropertiesShouldNotBeIgnored()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = true;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setExcluded();

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$fieldName]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForIgnoredField()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['field1']);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::never())
            ->method('createAndAddFieldMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForField()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$fieldName]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForRenamedField()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $propertyPath = 'testPropertyPath';
        $field = $config->addField($fieldName);
        $field->setPropertyPath($propertyPath);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$propertyPath]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForUnknownField()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $config->addField('unknownField');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['field1']);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::never())
            ->method('createAndAddFieldMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForMetaProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$fieldName]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddMetaPropertyMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForExcludedMetaProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);
        $field->setExcluded();

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$fieldName]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::never())
            ->method('createAndAddMetaPropertyMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForRenamedMetaProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $propertyPath = 'testPropertyPath';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);
        $field->setPropertyPath($propertyPath);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([$propertyPath]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddMetaPropertyMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForUnknownMetaProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $field = $config->addField('unknownField');
        $field->setMetaProperty(true);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['field1']);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::never())
            ->method('createAndAddMetaPropertyMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForAssociation()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $associationName = 'testAssociation';
        $field = $config->addField($associationName);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([$associationName]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $associationName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForExcludedAssociation()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $associationName = 'testAssociation';
        $field = $config->addField($associationName);
        $field->setExcluded();

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([$associationName]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::never())
            ->method('createAndAddMetaPropertyMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForRenamedAssociation()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $associationName = 'testAssociation';
        $propertyPath = 'testPropertyPath';
        $field = $config->addField($associationName);
        $field->setPropertyPath($propertyPath);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([$propertyPath]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::once())
            ->method('createAndAddAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $associationName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForUnknownAssociation()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $config->addField('unknownAssociation');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn(['association1']);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->entityMetadataFactory->expects(self::never())
            ->method('createAndAddAssociationMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForAdditionalPropertyWithoutDataType()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $config->addField($fieldName);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddMetaPropertyMetadata');
        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddFieldMetadata');
        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddAssociationMetadata');
        $this->nestedObjectMetadataFactory->expects(self::never())
            ->method('createAndAddNestedObjectMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForExcludedAdditionalProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);
        $field->setExcluded();

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddMetaPropertyMetadata');
        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddFieldMetadata');
        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddAssociationMetadata');
        $this->nestedObjectMetadataFactory->expects(self::never())
            ->method('createAndAddNestedObjectMetadata');

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForExcludedAdditionalPropertyWhenExcludedPropertiesShouldNotBeIgnored()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = true;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);
        $field->setExcluded();

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddMetaPropertyMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForAdditionalMetaProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddMetaPropertyMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForAdditionalFieldProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setDataType('string');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForAdditionalAssociationProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $associationName = 'testAssociation';
        $field = $config->addField($associationName);
        $field->setDataType('integer');
        $field->setTargetClass('Test\TargetClass');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $associationName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForAdditionalNestedObjectProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setDataType('nestedObject');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn([]);
        $classMetadata->expects(self::once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $this->metadataFactory->expects(self::once())
            ->method('createEntityMetadata')
            ->with(self::identicalTo($classMetadata))
            ->willReturn($entityMetadata);

        $this->nestedObjectMetadataFactory->expects(self::once())
            ->method('createAndAddNestedObjectMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                self::identicalTo($config),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $withExcludedProperties,
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }
}
