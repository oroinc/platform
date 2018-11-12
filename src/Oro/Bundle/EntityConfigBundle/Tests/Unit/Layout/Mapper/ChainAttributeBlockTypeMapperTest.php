<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Mapper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\ChainAttributeBlockTypeMapper;

class ChainAttributeBlockTypeMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainAttributeBlockTypeMapper */
    private $chainMapper;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
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
