<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\MassActionProviderPass;

class MassActionProviderPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    /** @var MassActionProviderPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new MassActionProviderPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testProcessServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(MassActionProviderPass::REGISTRY_SERVICE_ID)
            ->willReturn(false);

        $this->container->expects($this->never())->method('getDefinition');
        $this->container->expects($this->never())->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    public function testProcessServiceExistsProvidersNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(MassActionProviderPass::REGISTRY_SERVICE_ID)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(MassActionProviderPass::PROVIDER_TAG)
            ->willReturn([]);

        $this->container->expects($this->never())->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(MassActionProviderPass::REGISTRY_SERVICE_ID)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(MassActionProviderPass::PROVIDER_TAG)
            ->willReturn(['provider_service' => ['class' => '\stdClass']]);

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [MassActionProviderPass::REGISTRY_SERVICE_ID, $definition],
                    ['provider_service', $this->getMock('Symfony\Component\DependencyInjection\Definition')]
                ]
            );

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addProvider', $this->isType('array'));

        $this->compilerPass->process($this->container);
    }
}
