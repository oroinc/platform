<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataFactory;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectMetadataLoader;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader\ObjectNestedObjectMetadataFactory;

class ObjectMetadataLoaderTest extends LoaderTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $objectMetadataFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nestedObjectMetadataFactory;

    /** @var ObjectMetadataLoader */
    protected $objectMetadataLoader;

    protected function setUp()
    {
        $this->objectMetadataFactory = $this->getMockBuilder(ObjectMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->nestedObjectMetadataFactory = $this->getMockBuilder(ObjectNestedObjectMetadataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectMetadataLoader = new ObjectMetadataLoader(
            $this->objectMetadataFactory,
            $this->nestedObjectMetadataFactory
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

        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->objectMetadataFactory->expects(self::never())
            ->method('createAndAddFieldMetadata');

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

        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
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

        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
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

        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
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

        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
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

        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->objectMetadataFactory->expects(self::once())
            ->method('createAndAddAssociationMetadata')
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

        $this->objectMetadataFactory->expects(self::once())
            ->method('createObjectMetadata')
            ->with($entityClass, self::identicalTo($config))
            ->willReturn($entityMetadata);
        $this->nestedObjectMetadataFactory->expects(self::once())
            ->method('createAndAddNestedObjectMetadata')
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
