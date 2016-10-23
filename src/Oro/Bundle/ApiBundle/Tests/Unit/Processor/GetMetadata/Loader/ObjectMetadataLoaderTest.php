<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataBuilder;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectNestedObjectMetadataBuilder;

class ObjectMetadataLoaderTest extends LoaderTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $objectMetadataBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nestedObjectMetadataBuilder;

    /** @var ObjectMetadataLoader */
    protected $objectMetadataLoader;

    protected function setUp()
    {
        $this->objectMetadataBuilder = $this->getMockBuilder(ObjectMetadataBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nestedObjectMetadataBuilder = $this->getMockBuilder(ObjectNestedObjectMetadataBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectMetadataLoader = new ObjectMetadataLoader(
            $this->objectMetadataBuilder,
            $this->nestedObjectMetadataBuilder
        );
    }

    public function testForExcludedField()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $entityMetadata = new EntityMetadata();

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setExcluded();

        $this->objectMetadataBuilder->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->objectMetadataBuilder->expects(self::never())
            ->method('addFieldMetadata');

        $result = $this->objectMetadataLoader->loadObjectMetadata(
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

        $entityMetadata = new EntityMetadata();

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setExcluded();

        $this->objectMetadataBuilder->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->objectMetadataBuilder->expects(self::once())
            ->method('addFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->objectMetadataLoader->loadObjectMetadata(
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

        $entityMetadata = new EntityMetadata();

        $fieldName = 'testField';
        $field = $config->addField($fieldName);

        $this->objectMetadataBuilder->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->objectMetadataBuilder->expects(self::once())
            ->method('addFieldMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->objectMetadataLoader->loadObjectMetadata(
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

        $entityMetadata = new EntityMetadata();

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);

        $this->objectMetadataBuilder->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->objectMetadataBuilder->expects(self::once())
            ->method('addMetaPropertyMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->objectMetadataLoader->loadObjectMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertFalse($entityMetadata->isInheritedType());
    }

    public function testForClassMetaProperty()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $entityMetadata = new EntityMetadata();

        $fieldName = '__class__';
        $field = $config->addField($fieldName);
        $field->setMetaProperty(true);

        $this->objectMetadataBuilder->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->objectMetadataBuilder->expects(self::once())
            ->method('addMetaPropertyMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->objectMetadataLoader->loadObjectMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
        self::assertTrue($entityMetadata->isInheritedType());
    }

    public function testForAssociation()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $entityMetadata = new EntityMetadata();

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setTargetClass('Test\TargetClass');

        $this->objectMetadataBuilder->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->objectMetadataBuilder->expects(self::once())
            ->method('addAssociationMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $targetAction
            );

        $result = $this->objectMetadataLoader->loadObjectMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }

    public function testForNestedObject()
    {
        $entityClass = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $withExcludedProperties = false;
        $targetAction = 'testAction';

        $entityMetadata = new EntityMetadata();

        $fieldName = 'testField';
        $field = $config->addField($fieldName);
        $field->setDataType('nestedObject');

        $this->objectMetadataBuilder->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->nestedObjectMetadataBuilder->expects(self::once())
            ->method('addNestedObjectMetadata')
            ->with(
                self::identicalTo($entityMetadata),
                self::identicalTo($config),
                $entityClass,
                $fieldName,
                self::identicalTo($field),
                $withExcludedProperties,
                $targetAction
            );

        $result = $this->objectMetadataLoader->loadObjectMetadata(
            $entityClass,
            $config,
            $withExcludedProperties,
            $targetAction
        );
        self::assertSame($entityMetadata, $result);
    }
}
