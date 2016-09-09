<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler\ConfigProviderCompilerPass;
use Oro\Bundle\RequireJSBundle\Provider\Config as ConfigProvider;

class ConfigProviderCompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigProviderCompilerPass
     */
    protected $compilerPass;

    protected function setUp()
    {
        $this->compilerPass = new ConfigProviderCompilerPass();
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(ConfigProviderCompilerPass::PROVIDER_SERVICE)
            ->will($this->returnValue(true));

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $definition */
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition', [], [], '', false);
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ConfigProviderCompilerPass::PROVIDER_SERVICE)
            ->will($this->returnValue($definition));

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->getMock('Oro\Bundle\RequireJSBundle\Provider\Config', [], [], '', false);
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ConfigProviderCompilerPass::TAG_NAME)
            ->will($this->returnValue([
                'requirejs.config_provider' => [['alias' => 'oro_requirejs_config_provider']]
            ]));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addProvider', [new Reference('requirejs.config_provider'), 'oro_requirejs_config_provider']);

        $this->compilerPass->process($container);
    }
}
