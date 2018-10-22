<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler\ConfigProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ConfigProviderCompilerPassTest extends \PHPUnit\Framework\TestCase
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
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(ConfigProviderCompilerPass::PROVIDER_SERVICE)
            ->will($this->returnValue(true));

        /** @var Definition|\PHPUnit\Framework\MockObject\MockObject $definition */
        $definition = $this->createMock(Definition::class);
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ConfigProviderCompilerPass::PROVIDER_SERVICE)
            ->will($this->returnValue($definition));

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
