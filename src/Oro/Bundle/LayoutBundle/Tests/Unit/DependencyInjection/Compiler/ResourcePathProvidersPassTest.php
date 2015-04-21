<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LayoutBundle\DependencyInjection\Compiler\ResourcePathProvidersPass;

class ResourcePathProvidersPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResourcePathProvidersPass */
    protected $pass;

    protected function setUp()
    {
        $this->pass = new ResourcePathProvidersPass();
    }

    protected function tearDown()
    {
        unset($this->pass);
    }

    public function testChainDefinitionNotFound()
    {
        $container = new ContainerBuilder();
        $this->pass->process($container);
    }

    public function testNoTaggedServicesFound()
    {
        $definition = new Definition();

        $container = new ContainerBuilder();
        $container->setDefinition('oro_layout.loader.path_provider', $definition);

        $this->pass->process($container);

        $this->assertEmpty($definition->getMethodCalls());
    }

    public function testFoundProviders()
    {
        $definition = new Definition();

        $container = new ContainerBuilder();
        $container->setDefinition('oro_layout.loader.path_provider', $definition);

        $provider1Def = new Definition();
        $provider1Def->addTag('layout.resource.path_provider', ['priority' => 100]);
        $container->setDefinition('provider1', $provider1Def);
        $provider2Def = new Definition();
        $provider2Def->addTag('layout.resource.path_provider');
        $container->setDefinition('provider2', $provider2Def);

        $this->pass->process($container);

        $methods = $definition->getMethodCalls();
        $this->assertCount(2, $methods);
        $this->assertEquals(['addProvider', [new Reference('provider1'), 100]], current($methods));
        $this->assertEquals(['addProvider', [new Reference('provider2'), 0]], next($methods));
    }
}
