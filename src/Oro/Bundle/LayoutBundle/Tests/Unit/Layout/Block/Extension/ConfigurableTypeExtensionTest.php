<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ConfigurableTypeExtension;
use Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\ConfigurableBlockTestCase;
use Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension\Stubs\CustomType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

class ConfigurableTypeExtensionTest extends ConfigurableBlockTestCase
{
    /**
     * @var ConfigurableTypeExtension
     */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $this->type = new CustomType(ConfigurableBlockTestCase::TYPE_NAME);
        $this->extension = new ConfigurableTypeExtension();
        $this->extension->setExtendedType($this->type->getName())
            ->setOptionsConfig($this->getOptionsConfig());
        $layoutFactoryBuilder->addType($this->type);
        $layoutFactoryBuilder->addTypeExtension($this->extension);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Name of extended type should be provided for block type extension
     */
    public function testGetNameException()
    {
        (new ConfigurableTypeExtension())->getExtendedType();
    }

    public function testGetName()
    {
        $this->assertEquals(ConfigurableBlockTestCase::TYPE_NAME, $this->extension->getExtendedType());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Name of extended type should be a string, array given
     */
    public function testSetNameExceptionType()
    {
        $this->extension->setExtendedType([]);
    }
}
