<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ConfigurationPass;

class ConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $container   = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $registryDef = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $factoryDef  = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $serviceIds = [
            'block1' => [['class' => 'Test\Class1']],
            'block2' => [['class' => 'Test\Class2', 'alias' => 'test_block_name']]
        ];

        $container->expects($this->exactly(4))
            ->method('hasDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::BLOCK_RENDERER_REGISTRY_SERVICE, true],
                        [ConfigurationPass::PHP_BLOCK_RENDERER_SERVICE, true],
                        [ConfigurationPass::TWIG_BLOCK_RENDERER_SERVICE, true],
                        [ConfigurationPass::BLOCK_TYPE_FACTORY_SERVICE, true]
                    ]
                )
            );
        $container->expects($this->exactly(2))
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        [ConfigurationPass::BLOCK_RENDERER_REGISTRY_SERVICE, $registryDef],
                        [ConfigurationPass::BLOCK_TYPE_FACTORY_SERVICE, $factoryDef]
                    ]
                )
            );

        $registryDef->expects($this->at(0))
            ->method('addMethodCall')
            ->with(
                'addRenderer',
                ['php', new Reference(ConfigurationPass::PHP_BLOCK_RENDERER_SERVICE)]
            );
        $registryDef->expects($this->at(1))
            ->method('addMethodCall')
            ->with(
                'addRenderer',
                ['twig', new Reference(ConfigurationPass::TWIG_BLOCK_RENDERER_SERVICE)]
            );

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(ConfigurationPass::BLOCK_TYPE_TAG_NAME))
            ->will($this->returnValue($serviceIds));
        $factoryDef->expects($this->once())
            ->method('replaceArgument')
            ->with(1, ['block1' => 'block1', 'test_block_name' => 'block2']);

        $compilerPass = new ConfigurationPass();
        $compilerPass->process($container);
    }
}
