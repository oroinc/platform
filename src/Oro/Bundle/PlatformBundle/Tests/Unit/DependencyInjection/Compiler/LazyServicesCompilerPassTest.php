<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\Yaml\Parser;

use Oro\Bundle\PlatformBundle\DependencyInjection\Compiler\LazyServicesCompilerPass;

class LazyServicesCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $expectedServices = array(
        'assetic.asset_manager',
        'knp_menu.renderer.twig',
        'templating',
        'twig',
        'templating.engine.twig',
        'twig.controller.exception',
    );

    public function testProcess()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        foreach ($this->expectedServices as $iteration => $serviceId) {
            $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');
            $definition->expects($this->once())->method('setLazy')->with(true);

            $container->expects($this->at($iteration * 2))->method('hasDefinition')->with($serviceId)
                ->will($this->returnValue(true));
            $container->expects($this->at($iteration * 2 + 1))->method('getDefinition')->with($serviceId)
                ->will($this->returnValue($definition));
        }

        $compiler = new LazyServicesCompilerPass();
        $compiler->process($container);
    }
}
