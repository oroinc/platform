<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\Layout\Block\Extension\ConfigurableTypeExtension;
use Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\ConfigurableBlockTestCase;
use Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension\Stubs\CustomType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

class ConfigurableTypeExtensionTest extends ConfigurableBlockTestCase
{
    /** @var ConfigurableTypeExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $this->type = new CustomType(self::TYPE_NAME);
        $this->extension = new ConfigurableTypeExtension();
        $this->extension->setExtendedType($this->type->getName())
            ->setOptionsConfig($this->getOptionsConfig());
        $layoutFactoryBuilder->addType($this->type);
        $layoutFactoryBuilder->addTypeExtension($this->extension);
    }

    public function testGetNameException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Name of extended type should be provided for block type extension');

        (new ConfigurableTypeExtension())->getExtendedType();
    }

    public function testGetName()
    {
        $this->assertEquals(self::TYPE_NAME, $this->extension->getExtendedType());
    }

    public function testSetNameExceptionType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name of extended type should be a string, array given');

        $this->extension->setExtendedType([]);
    }
}
