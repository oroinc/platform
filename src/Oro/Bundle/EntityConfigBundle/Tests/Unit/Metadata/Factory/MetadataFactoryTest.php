<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Metadata\Driver\AnnotationDriver;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\Factory\MetadataFactory;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Factory\Fixture as Entity;

class MetadataFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var MetadataFactory */
    private $metadataFactory;

    protected function setUp(): void
    {
        $this->metadataFactory = new MetadataFactory(new AnnotationDriver(new AnnotationReader()));
    }

    private function getFieldMetadata(
        string $class,
        string $name,
        string $mode = null,
        array $defaultValues = null
    ): FieldMetadata {
        $fieldMetadata = new FieldMetadata($class, $name);
        $fieldMetadata->mode = $mode;
        $fieldMetadata->defaultValues = $defaultValues;

        return $fieldMetadata;
    }

    public function testForNotExistingEntity(): void
    {
        $this->expectException(\ReflectionException::class);
        $this->metadataFactory->getMetadataForClass('Test\NotExisting');
    }

    public function testForNotConfigurableEntity(): void
    {
        $metadata = $this->metadataFactory->getMetadataForClass(Entity\NotConfigurableEntity::class);
        self::assertNull($metadata);
        // test memory cache
        self::assertNull($this->metadataFactory->getMetadataForClass(Entity\NotConfigurableEntity::class));
    }

    public function testForConfigurableEntityWithEmptyConfig(): void
    {
        $entityClass = Entity\ConfigurableEntityWithEmptyConfig::class;
        $expectedMetadata = new EntityMetadata($entityClass);
        $expectedMetadata->mode = ConfigModel::MODE_DEFAULT;
        $expectedMetadata->defaultValues = [];
        $expectedMetadata->routeName = '';
        $expectedMetadata->routeView = '';
        $expectedMetadata->routeCreate = '';
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'id',
            ConfigModel::MODE_DEFAULT,
            []
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'name',
            ConfigModel::MODE_DEFAULT,
            []
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'label'
        ));

        $metadata = $this->metadataFactory->getMetadataForClass($entityClass);
        self::assertEquals($expectedMetadata, $metadata);
        // test memory cache
        self::assertSame($metadata, $this->metadataFactory->getMetadataForClass($entityClass));
    }

    public function testForConfigurableEntity(): void
    {
        $entityClass = Entity\ConfigurableEntity::class;
        $parentEntityClass = Entity\ParentEntity::class;
        $expectedMetadata = new EntityMetadata($entityClass);
        $expectedMetadata->mode = ConfigModel::MODE_DEFAULT;
        $expectedMetadata->defaultValues = ['scope' => ['key' => 'value']];
        $expectedMetadata->routeName = '';
        $expectedMetadata->routeView = '';
        $expectedMetadata->routeCreate = '';
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'id',
            ConfigModel::MODE_DEFAULT,
            ['scope' => ['key' => 'value']]
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'name',
            ConfigModel::MODE_DEFAULT,
            ['scope' => ['key' => 'value']]
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'label'
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $parentEntityClass,
            'parentName',
            ConfigModel::MODE_READONLY,
            ['scope' => ['key' => 'parentValue']]
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $parentEntityClass,
            'parentLabel'
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'privateFieldWithConfigInParent',
            ConfigModel::MODE_DEFAULT,
            ['scope' => ['key' => 'value']]
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'fieldWithConfigInParent1'
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'fieldWithConfigInParent2',
            ConfigModel::MODE_DEFAULT,
            ['scope' => ['key' => 'value']]
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'fieldWithoutConfigInParent1',
            ConfigModel::MODE_DEFAULT,
            ['scope' => ['key' => 'value']]
        ));
        $expectedMetadata->addFieldMetadata($this->getFieldMetadata(
            $entityClass,
            'fieldWithoutConfigInParent2'
        ));

        $metadata = $this->metadataFactory->getMetadataForClass($entityClass);
        self::assertEquals($expectedMetadata, $metadata);
        // test memory cache
        self::assertSame($metadata, $this->metadataFactory->getMetadataForClass($entityClass));
    }
}
