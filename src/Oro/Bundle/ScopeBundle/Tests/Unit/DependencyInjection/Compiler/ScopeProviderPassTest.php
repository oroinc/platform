<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ScopeBundle\DependencyInjection\Compiler\ScopeProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScopeProviderPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeProviderPass
     */
    protected $compilerPass;

    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $containerBuilder;

    public function setUp()
    {
        $this->containerBuilder = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->compilerPass = new ScopeProviderPass();
    }

    public function tearDown()
    {
        unset($this->compilerPass, $this->containerBuilder);
    }

    public function testProcessRegistryDoesNotExist()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(ScopeProviderPass::SCOPE_MANAGER)
            ->willReturn(false);

        $this->containerBuilder
            ->expects($this->never())
            ->method('getDefinition');

        $this->containerBuilder
            ->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoTaggedServicesFound()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(ScopeProviderPass::SCOPE_MANAGER)
            ->willReturn(true);

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn([]);

        $this->containerBuilder
            ->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithTaggedServices()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(ScopeProviderPass::SCOPE_MANAGER)
            ->willReturn(true);

        $registryServiceDefinition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(ScopeProviderPass::SCOPE_MANAGER)
            ->willReturn($registryServiceDefinition);

        $taggedServices = [
            'service.name.1' => [
                ['scopeType' => 'scope', 'priority' => 100],
                ['scopeType' => 'scope2', 'priority' => 1],
            ],
            'service.name.2' => [
                ['scopeType' => 'scope', 'priority' => 1],
                ['scopeType' => 'scope2', 'priority' => 100]
            ],
            'service.name.3' => [['scopeType' => 'scope', 'priority' => 200]],
        ];

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);

        $registryServiceDefinition
            ->expects($this->exactly(5))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addProvider', ['scope', new Reference('service.name.3')]],
                ['addProvider', ['scope', new Reference('service.name.1')]],
                ['addProvider', ['scope', new Reference('service.name.2')]],
                ['addProvider', ['scope2', new Reference('service.name.2')]],
                ['addProvider', ['scope2', new Reference('service.name.1')]]
            );

        $this->compilerPass->process($this->containerBuilder);
    }
}
