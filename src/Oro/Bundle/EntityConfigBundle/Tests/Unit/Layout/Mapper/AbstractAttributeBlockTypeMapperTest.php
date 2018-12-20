<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Mapper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\Layout\Mapper\AttributeBlockTypeMapperStub;

class AbstractAttributeBlockTypeMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttributeBlockTypeMapperStub */
    private $mapper;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $this->mapper = new AttributeBlockTypeMapperStub($this->registry);
    }

    public function testGetBlockTypeFromAttributeTypesRegistry()
    {
        $this->mapper->addBlockType('string', 'attribute_string');

        $attribute = new FieldConfigModel();
        $attribute->setType('string');

        $this->assertEquals('attribute_string', $this->mapper->getBlockType($attribute));
    }

    public function testGetBlockTypeFromTargetClassesRegistry()
    {
        $this->mapper->addBlockTypeUsingMetadata(\stdClass::class, 'attribute_std_class');

        $entity = new EntityConfigModel();
        $entity->setClassName('EntityClass');

        $attribute = new FieldConfigModel();
        $attribute->setEntity($entity);
        $attribute->setFieldName('metadata_attribute');

        $metadata = $this->getMockBuilder(ClassMetadata::class)->getMock();
        $metadata->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['metadata_attribute']);

        $metadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with('metadata_attribute')
            ->willReturn(\stdClass::class);

        $objectManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with('EntityClass')
            ->willReturn($metadata);

        $this->registry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('EntityClass')
            ->willReturn($objectManager);

        $this->assertEquals('attribute_std_class', $this->mapper->getBlockType($attribute));
    }

    public function testGetBlockTypeNull()
    {
        $attribute = new FieldConfigModel();
        $attribute->setType('attribute_string');

        $this->assertNull($this->mapper->getBlockType($attribute));
    }
}
