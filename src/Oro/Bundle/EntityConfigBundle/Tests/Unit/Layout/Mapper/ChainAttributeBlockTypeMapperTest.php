<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Mapper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\ChainAttributeBlockTypeMapper;

class ChainAttributeBlockTypeMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
    }

    private function getChainMapper(array $mappers = []): ChainAttributeBlockTypeMapper
    {
        $chainMapper = new ChainAttributeBlockTypeMapper($this->registry, $mappers);
        $chainMapper->setDefaultBlockType('default_block_type');

        return $chainMapper;
    }

    public function testGetBlockTypeFromProvider()
    {
        $attribute = new FieldConfigModel();
        $attribute->setType('string');

        $mapper = $this->createMock(AttributeBlockTypeMapperInterface::class);
        $mapper->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn('attribute_string');

        $chainMapper = $this->getChainMapper([$mapper]);
        $chainMapper->addBlockType('int', 'attribute_int');

        $this->assertEquals('attribute_string', $chainMapper->getBlockType($attribute));
    }

    public function testGetBlockTypeDefault()
    {
        $attribute = new FieldConfigModel();
        $attribute->setType('percent');

        $mapper = $this->createMock(AttributeBlockTypeMapperInterface::class);
        $mapper->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn(null);

        $chainMapper = $this->getChainMapper([$mapper]);
        $chainMapper->addBlockType('string', 'attribute_string');

        $this->assertEquals('default_block_type', $chainMapper->getBlockType($attribute));
    }
}
