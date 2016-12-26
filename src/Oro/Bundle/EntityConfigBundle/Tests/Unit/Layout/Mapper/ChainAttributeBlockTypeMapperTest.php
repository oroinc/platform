<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Mapper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\ChainAttributeBlockTypeMapper;

class ChainAttributeBlockTypeMapperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainAttributeBlockTypeMapper */
    private $chainMapper;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $this->chainMapper = new ChainAttributeBlockTypeMapper($this->registry);
        $this->chainMapper->setDefaultBlockType('default_block_type');
    }

    public function testGetBlockTypeFromProvider()
    {
        $this->chainMapper->addBlockType('int', 'attribute_int');

        $attribute = new FieldConfigModel();
        $attribute->setType('string');

        $mapper = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $mapper->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn('attribute_string');

        $this->chainMapper->addMapper($mapper);

        $this->assertEquals('attribute_string', $this->chainMapper->getBlockType($attribute));
    }

    public function testGetBlockTypeFromAttributeTypesRegistry()
    {
        $this->chainMapper->addBlockType('string', 'attribute_string');

        $attribute = new FieldConfigModel();
        $attribute->setType('string');

        $mapper = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $mapper->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn(null);

        $this->chainMapper->addMapper($mapper);

        $this->assertEquals('attribute_string', $this->chainMapper->getBlockType($attribute));
    }

    public function testGetBlockTypeFromTargetClassesRegistry()
    {
        $this->chainMapper->addBlockTypeUsingMetadata(\stdClass::class, 'attribute_std_class');

        $entity = new EntityConfigModel();
        $entity->setClassName('EntityClass');

        $attribute = new FieldConfigModel();
        $attribute->setEntity($entity);

        $mapper = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $mapper->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn(null);

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

        $this->chainMapper->addMapper($mapper);

        $this->assertEquals('attribute_std_class', $this->chainMapper->getBlockType($attribute));
    }

    public function testGetBlockTypeDefault()
    {
        $this->chainMapper->addBlockType('string', 'attribute_string');

        $attribute = new FieldConfigModel();
        $attribute->setType('percent');

        $mapper = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $mapper->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn(null);

        $this->chainMapper->addMapper($mapper);

        $this->assertEquals('default_block_type', $this->chainMapper->getBlockType($attribute));

    }
}
