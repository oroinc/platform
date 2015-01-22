<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DependencyInjectionBlockTypeFactory;

class DependencyInjectionBlockTypeFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateBlockType()
    {
        $widgetBlockType = $this->getMock('Oro\Component\Layout\BlockTypeInterface');
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($widgetBlockType));
        $blockTypeFactory = new DependencyInjectionBlockTypeFactory(
            $container,
            ['widget' => 'oro_layout.block_type_widget']
        );

        $this->assertSame($widgetBlockType, $blockTypeFactory->createBlockType('widget'));
    }
}
