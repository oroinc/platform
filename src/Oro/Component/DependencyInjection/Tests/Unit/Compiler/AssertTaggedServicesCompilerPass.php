<?php

namespace Oro\Component\DependencyInjection\Tests\Unit\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AssertTaggedServicesCompilerPass extends \PHPUnit_Framework_TestCase
{
    /**
     * @param CompilerPassInterface $compilerPass
     * @param string $serviceId
     * @param string $tagName
     * @param string $addMethodName
     */
    public function assertTaggedServicesRegistered(
        CompilerPassInterface $compilerPass,
        $serviceId,
        $tagName,
        $addMethodName
    ) {
        $this->assetProcessSkipWithoutServiceDefinition($compilerPass, $serviceId);
        $this->assertProcessSkipWithoutTaggedServices($compilerPass, $serviceId, $tagName);
        $this->assertProcess($compilerPass, $serviceId, $tagName, $addMethodName);
    }

    /**
     * @param CompilerPassInterface $compilerPass
     * @param string $serviceId
     */
    private function assetProcessSkipWithoutServiceDefinition(CompilerPassInterface $compilerPass, $serviceId)
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with($serviceId)
            ->willReturn(false);

        $compilerPass->process($containerBuilder);
    }

    /**
     * @param CompilerPassInterface $compilerPass
     * @param string $serviceId
     * @param string $tagName
     */
    private function assertProcessSkipWithoutTaggedServices(CompilerPassInterface $compilerPass, $serviceId, $tagName)
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with($serviceId)
            ->willReturn(true);

        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($tagName)
            ->willReturn(null);

        $containerBuilder->expects($this->never())
            ->method('getDefinition')
            ->with($serviceId);

        $compilerPass->process($containerBuilder);
    }

    /**
     * @param CompilerPassInterface $compilerPass
     * @param string $serviceId
     * @param string $tagName
     * @param string $addMethodName
     */
    private function assertProcess(CompilerPassInterface $compilerPass, $serviceId, $tagName, $addMethodName)
    {
        $service = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $service->expects($this->exactly(3))->method('addMethodCall');

        $service->expects($this->at(0))
            ->method('addMethodCall')
            ->with($addMethodName, [new Reference('taggedService2'), 'taggedService2']);

        $service->expects($this->at(1))
            ->method('addMethodCall')
            ->with($addMethodName, [new Reference('taggedService3'), 'taggedService3Alias']);

        $service->expects($this->at(2))
            ->method('addMethodCall')
            ->with($addMethodName, [new Reference('taggedService1'), 'taggedService1Alias']);

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with($serviceId)
            ->willReturn(true);

        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($tagName)
            ->willReturn([
                'taggedService1' => [
                    ['priority' => 20, 'alias' => 'taggedService1Alias'],
                ],
                'taggedService2' => [
                ],
                'taggedService3' => [
                    ['priority' => 10, 'alias' => 'taggedService3Alias'],
                ],
            ]);

        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with($serviceId)
            ->willReturn($service);

        $compilerPass->process($containerBuilder);
    }
}
