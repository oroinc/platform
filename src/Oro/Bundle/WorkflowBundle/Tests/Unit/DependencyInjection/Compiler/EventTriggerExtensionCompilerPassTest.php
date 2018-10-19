<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\EventTriggerExtensionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EventTriggerExtensionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    const TAGGED_SERVICE_1 = 'test.tagged.service.first';
    const TAGGED_SERVICE_2 = 'test.tagged.service.second';

    public function testProcess()
    {
        $listener = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();
        $listener->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addExtension', [new Reference(self::TAGGED_SERVICE_1)]);
        $listener->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addExtension', [new Reference(self::TAGGED_SERVICE_2)]);

        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();
        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(EventTriggerExtensionCompilerPass::LISTENER_SERVICE)
            ->willReturn(true);
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(EventTriggerExtensionCompilerPass::LISTENER_SERVICE)
            ->willReturn($listener);
        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(EventTriggerExtensionCompilerPass::EXTENSION_TAG)
            ->willReturn(
                [
                    self::TAGGED_SERVICE_1 => [[]],
                    self::TAGGED_SERVICE_2 => [[]],
                ]
            );

        $compilerPass = new EventTriggerExtensionCompilerPass();
        $compilerPass->process($containerBuilder);
    }

    public function testProcessWithoutListenerDefinition()
    {
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)->disableOriginalConstructor()->getMock();
        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(EventTriggerExtensionCompilerPass::LISTENER_SERVICE)
            ->willReturn(false);
        $containerBuilder->expects($this->never())->method('getDefinition');
        $containerBuilder->expects($this->never())->method('findTaggedServiceIds');

        $compilerPass = new EventTriggerExtensionCompilerPass();
        $compilerPass->process($containerBuilder);
    }
}
