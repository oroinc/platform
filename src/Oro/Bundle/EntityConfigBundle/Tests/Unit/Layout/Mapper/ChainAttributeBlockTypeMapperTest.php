<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\ChainAttributeBlockTypeMapper;

class ChainAttributeBlockTypeMapperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChainAttributeBlockTypeMapper */
    private $chainMapper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->chainMapper = new ChainAttributeBlockTypeMapper();
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

        $mapper->expects($this->once())
            ->method('supports')
            ->with($attribute)
            ->willReturn(true);

        $this->chainMapper->addMapper($mapper);

        $this->assertEquals('attribute_string', $this->chainMapper->getBlockType($attribute));
    }

    public function testGetBlockTypeFromProviderNotSupported()
    {
        $this->chainMapper->addBlockType('int', 'attribute_int');

        $attribute = new FieldConfigModel();
        $attribute->setType('string');
        $attribute->setFieldName('string_field');

        $mapper = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $mapper->expects($this->never())
            ->method('getBlockType');

        $mapper->expects($this->once())
            ->method('supports')
            ->with($attribute)
            ->willReturn(false);

        $this->chainMapper->addMapper($mapper);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Block type is not define for field "string_field"');

        $this->chainMapper->getBlockType($attribute);
    }

    public function testGetBlockTypeFromRegistry()
    {
        $this->chainMapper->addBlockType('string', 'attribute_string');

        $attribute = new FieldConfigModel();
        $attribute->setType('string');

        $mapper = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $mapper->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn(null);

        $mapper->expects($this->once())
            ->method('supports')
            ->with($attribute)
            ->willReturn(true);

        $this->chainMapper->addMapper($mapper);

        $this->assertEquals('attribute_string', $this->chainMapper->getBlockType($attribute));
    }

    public function testGetBlockTypeLogicException()
    {
        $this->chainMapper->addBlockType('string', 'attribute_string');

        $attribute = new FieldConfigModel();
        $attribute->setType('percent');
        $attribute->setFieldName('percent_field');

        $mapper = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $mapper->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn(null);

        $mapper->expects($this->once())
            ->method('supports')
            ->with($attribute)
            ->willReturn(true);

        $this->chainMapper->addMapper($mapper);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Block type is not define for field "percent_field"');

        $this->chainMapper->getBlockType($attribute);

    }
}
