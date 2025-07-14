<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\Layout\Mapper\AttributeBlockTypeMapperStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractAttributeBlockTypeMapperTest extends TestCase
{
    private AttributeBlockTypeMapperStub $mapper;
    private ManagerRegistry&MockObject $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->mapper = new AttributeBlockTypeMapperStub($this->registry);
    }

    public function testGetBlockTypeFromAttributeTypesRegistry(): void
    {
        $this->mapper->addBlockType('string', 'attribute_string');

        $attribute = new FieldConfigModel();
        $attribute->setType('string');

        $this->assertEquals('attribute_string', $this->mapper->getBlockType($attribute));
    }

    public function testGetBlockTypeFromTargetClassesRegistry(): void
    {
        $this->mapper->addBlockTypeUsingMetadata(\stdClass::class, 'attribute_std_class');

        $entity = new EntityConfigModel();
        $entity->setClassName('EntityClass');

        $attribute = new FieldConfigModel();
        $attribute->setEntity($entity);
        $attribute->setFieldName('metadata_attribute');

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['metadata_attribute']);

        $metadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('metadata_attribute')
            ->willReturn(\stdClass::class);

        $objectManager = $this->createMock(EntityManager::class);
        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with('EntityClass')
            ->willReturn($metadata);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('EntityClass')
            ->willReturn($objectManager);

        $this->assertEquals('attribute_std_class', $this->mapper->getBlockType($attribute));
    }

    public function testGetBlockTypeNull(): void
    {
        $attribute = new FieldConfigModel();
        $attribute->setType('attribute_string');

        $this->assertNull($this->mapper->getBlockType($attribute));
    }
}
