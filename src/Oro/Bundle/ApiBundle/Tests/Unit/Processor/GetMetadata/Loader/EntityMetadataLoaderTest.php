<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadataFactory as MetadataFactory;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityNestedAssociationMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\EntityNestedObjectMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EntityMetadataLoaderTest extends LoaderTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataFactory */
    private $metadataFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectMetadataFactory */
    private $objectMetadataFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityMetadataFactory */
    private $entityMetadataFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityNestedObjectMetadataFactory */
    private $nestedObjectMetadataFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityNestedAssociationMetadataFactory */
    private $nestedAssociationMetadataFactory;

    /** @var EntityMetadataLoader */
    private $entityMetadataLoader;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->metadataFactory = $this->createMock(MetadataFactory::class);
        $this->objectMetadataFactory = $this->createMock(ObjectMetadataFactory::class);
        $this->entityMetadataFactory = $this->createMock(EntityMetadataFactory::class);
        $this->nestedObjectMetadataFactory = $this->createMock(EntityNestedObjectMetadataFactory::class);
        $this->nestedAssociationMetadataFactory = $this->createMock(EntityNestedAssociationMetadataFactory::class);

        $this->entityMetadataLoader = new EntityMetadataLoader(
            $this->doctrineHelper,
            new EntityIdHelper(),
            $this->metadataFactory,
            $this->objectMetadataFactory,
            $this->entityMetadataFactory,
            $this->nestedObjectMetadataFactory,
            $this->nestedAssociationMetadataFactory
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

    public function testForConfiguredIdentifierField()
    {
        $entityClass = 'Test\Class';
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['field1']);
        $field1 = $config->addField('field1');
        $field1->setPropertyPath('realField1');
        $field2 = $config->addField('id');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $entityMetadata->setIdentifierFieldNames(['id']);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'realField1']);
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

        $this->entityMetadataFactory->expects(self::at(0))
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                'id',
                self::identicalTo($field2),
                $targetAction
            );
        $this->entityMetadataFactory->expects(self::at(1))
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                'field1',
                self::identicalTo($field1),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertEquals(['field1'], $entityMetadata->getIdentifierFieldNames());
    }

    public function testForConfiguredIdentifierFieldShouldSetHasIdentifierGeneratorToFalse()
    {
        $entityClass = 'Test\Class';
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['field1']);
        $field1 = $config->addField('field1');
        $field2 = $config->addField('id');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->setHasIdentifierGenerator(true);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'field1']);
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

        $this->entityMetadataFactory->expects(self::at(0))
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                'id',
                self::identicalTo($field2),
                $targetAction
            );
        $this->entityMetadataFactory->expects(self::at(1))
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                'field1',
                self::identicalTo($field1),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertEquals(['field1'], $entityMetadata->getIdentifierFieldNames());
        self::assertFalse($entityMetadata->hasIdentifierGenerator());
    }

    public function testForConfiguredIdentifierFieldEqualsToEntityIdentifierFieldShouldKeepHasIdentifierGeneratorAsIs()
    {
        $entityClass = 'Test\Class';
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $field1 = $config->addField('field1');
        $field2 = $config->addField('id');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $entityMetadata->setIdentifierFieldNames(['id']);
        $entityMetadata->setHasIdentifierGenerator(true);

        $classMetadata = $this->getClassMetadataMock($entityClass);
        $classMetadata->expects(self::once())
            ->method('getFieldNames')
            ->willReturn(['id', 'field1']);
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

        $this->entityMetadataFactory->expects(self::at(0))
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                'id',
                self::identicalTo($field2),
                $targetAction
            );
        $this->entityMetadataFactory->expects(self::at(1))
            ->method('createAndAddFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                'field1',
                self::identicalTo($field1),
                $targetAction
            );

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertEquals(['id'], $entityMetadata->getIdentifierFieldNames());
        self::assertTrue($entityMetadata->hasIdentifierGenerator());
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

    public function testForAssociationDoesNotExistInEntityAndConfiguredByTargetClassAndTargetType()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $associationName = 'testAssociation';
        $field = $config->addField($associationName);
        $field->setTargetClass('Test\AssociationTargetClass');
        $field->setTargetType('to-one');

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
                self::identicalTo($config),
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
        $this->nestedAssociationMetadataFactory->expects(self::never())
            ->method('createAndAddNestedAssociationMetadata');

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
        $this->nestedAssociationMetadataFactory->expects(self::never())
            ->method('createAndAddNestedAssociationMetadata');

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
                self::identicalTo($config),
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

    public function testForAdditionalNestedAssociationProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setDataType('nestedAssociation');

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

        $this->nestedAssociationMetadataFactory->expects(self::once())
            ->method('createAndAddNestedAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
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

    public function testForFieldWithConfiguredDirection()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setDirection('output-only');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $fieldMetadata = new FieldMetadata($fieldName);

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
            )
            ->willReturn($fieldMetadata);

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($fieldMetadata->isInput());
        self::assertTrue($fieldMetadata->isOutput());
    }

    public function testForMetaPropertyWithConfiguredDirection()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);
        $field->setDirection('output-only');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $propertyMetadata = new MetaPropertyMetadata($fieldName);

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
            )
            ->willReturn($propertyMetadata);

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($propertyMetadata->isInput());
        self::assertTrue($propertyMetadata->isOutput());
    }

    public function testForAssociationWithConfiguredDirection()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $associationName = 'testAssociation';
        $field = $config->addField($associationName);
        $field->setDirection('output-only');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $associationMetadata = new AssociationMetadata($associationName);

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
            )
            ->willReturn($associationMetadata);

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($associationMetadata->isInput());
        self::assertTrue($associationMetadata->isOutput());
    }

    public function testForFieldDoesNotExistInEntityAndConfiguredDirection()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setDataType('string');
        $field->setDirection('output-only');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $fieldMetadata = new FieldMetadata($fieldName);

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
            )
            ->willReturn($fieldMetadata);

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($fieldMetadata->isInput());
        self::assertTrue($fieldMetadata->isOutput());
    }

    public function testForMetaPropertyDoesNotExistInEntityAndConfiguredDirection()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);
        $field->setDirection('output-only');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $propertyMetadata = new MetaPropertyMetadata($fieldName);

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
            )
            ->willReturn($propertyMetadata);

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($propertyMetadata->isInput());
        self::assertTrue($propertyMetadata->isOutput());
    }

    public function testForAssociationDoesNotExistInEntityAndConfiguredDirection()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $associationName = 'testAssociation';
        $field = $config->addField($associationName);
        $field->setTargetClass('Test\AssociationTargetClass');
        $field->setTargetType('to-one');
        $field->setDirection('output-only');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $associationMetadata = new AssociationMetadata($associationName);

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
                self::identicalTo($config),
                $associationName,
                self::identicalTo($field),
                $targetAction
            )
            ->willReturn($associationMetadata);

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($associationMetadata->isInput());
        self::assertTrue($associationMetadata->isOutput());
    }

    public function testForNestedObjectPropertyWithConfiguredDirection()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setDataType('nestedObject');
        $field->setDirection('output-only');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $associationMetadata = new AssociationMetadata($fieldName);

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
            )
            ->willReturn($associationMetadata);

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($associationMetadata->isInput());
        self::assertTrue($associationMetadata->isOutput());
    }

    public function testForNestedAssociationPropertyWithConfiguredDirection()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setDataType('nestedAssociation');
        $field->setDirection('output-only');

        $entityMetadata = new EntityMetadata();
        $entityMetadata->setClassName($entityClass);
        $associationMetadata = new AssociationMetadata($fieldName);

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

        $this->nestedAssociationMetadataFactory->expects(self::once())
            ->method('createAndAddNestedAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($classMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $withExcludedProperties,
                $targetAction
            )
            ->willReturn($associationMetadata);

        $result = $this->entityMetadataLoader->loadEntityMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($associationMetadata->isInput());
        self::assertTrue($associationMetadata->isOutput());
    }
}
