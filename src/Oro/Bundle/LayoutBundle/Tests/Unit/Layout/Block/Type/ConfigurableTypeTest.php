<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\ConfigurableBlockTestCase;
use Oro\Bundle\LayoutBundle\Layout\Block\Type\ConfigurableType;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

class ConfigurableTypeTest extends ConfigurableBlockTestCase
{
    /**
     * @var ConfigurableType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $this->type = new ConfigurableType();
        $this->type->setName(ConfigurableBlockTestCase::TYPE_NAME)
            ->setParent(BaseType::NAME)
            ->setOptionsConfig($this->getOptionsConfig());
        $layoutFactoryBuilder->addType($this->type);
    }

    public function testSetOptionException()
    {
        $this->assertSetOptionException($this->type);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Name of block type does not configured
     */
    public function testGetNameException()
    {
        (new ConfigurableType())->getName();
    }

    public function testGetName()
    {
        $this->assertEquals(ConfigurableBlockTestCase::TYPE_NAME, $this->type->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Name of block type should be a string, array given
     */
    public function testSetNameExceptionType()
    {
        $this->type->setName([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Name of parent block type should be a string, array given
     */
    public function testSetParentExceptionType()
    {
        $this->type->setParent([]);
    }

    public function testGetParent()
    {
        $this->assertEquals(BaseType::NAME, $this->type->getParent());
        $this->type->setParent('test');
        $this->assertEquals('test', $this->type->getParent());
    }
}
