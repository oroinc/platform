<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AttributeBlockTypeMapperInterface;

class AbstractAttributeBlockTypeMapperTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttributeBlockTypeMapperStub */
    private $mapper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->mapper = new AttributeBlockTypeMapperStub();
    }

    public function testGetBlockTypeFromProvider()
    {
        $this->mapper->addBlockType('int', 'attribute_int');

        $attribute = new FieldConfigModel();
        $attribute->setType('string');

        $provider = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $provider->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn('attribute_string');

        $this->mapper->addProvider($provider);

        $this->assertEquals('attribute_string', $this->mapper->getBlockType($attribute));
    }

    public function testGetBlockTypeFromRegistry()
    {
        $this->mapper->addBlockType('string', 'attribute_string');

        $attribute = new FieldConfigModel();
        $attribute->setType('string');

        $provider = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $provider->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn(null);

        $this->mapper->addProvider($provider);

        $this->assertEquals('attribute_string', $this->mapper->getBlockType($attribute));
    }

    public function testGetBlockTypeLogicException()
    {
        $this->mapper->addBlockType('string', 'attribute_string');

        $attribute = new FieldConfigModel();
        $attribute->setType('percent');
        $attribute->setFieldName('percent_field');

        $provider = $this->getMockBuilder(AttributeBlockTypeMapperInterface::class)->getMock();
        $provider->expects($this->once())
            ->method('getBlockType')
            ->with($attribute)
            ->willReturn(null);

        $this->mapper->addProvider($provider);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Block type is not define for field "percent_field"');

        $this->mapper->getBlockType($attribute);

    }
}
