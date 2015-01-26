<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\BlockTypePass;

class BlockTypePassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container  = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $factoryDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $serviceIds = [
            'block1' => [['class' => 'Test\Class1']],
            'block2' => [['class' => 'Test\Class2', 'alias' => 'test_block_name']],
        ];

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(BlockTypePass::FACTORY_SERVICE_ID))
            ->will($this->returnValue(true));
        $container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(BlockTypePass::FACTORY_SERVICE_ID))
            ->will($this->returnValue($factoryDef));
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(BlockTypePass::TAG_NAME))
            ->will($this->returnValue($serviceIds));

        $factoryDef->expects($this->once())
            ->method('replaceArgument')
            ->with(1, ['block1' => 'block1', 'test_block_name' => 'block2']);

        $compilerPass = new BlockTypePass();
        $compilerPass->process($container);
    }
}
