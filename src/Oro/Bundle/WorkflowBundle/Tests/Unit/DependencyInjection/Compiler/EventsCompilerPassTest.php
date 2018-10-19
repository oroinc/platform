<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\EventsCompilerPass;
use Oro\Bundle\WorkflowBundle\Migrations\Data\ORM\LoadWorkflowNotificationEvents;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class EventsCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventsCompilerPass */
    protected $compilerPass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $containerBuilder;

    /** @var Definition|\PHPUnit\Framework\MockObject\MockObject */
    protected $definition;

    protected function setUp()
    {
        $this->compilerPass = new EventsCompilerPass();

        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
        $this->definition = $this->createMock(Definition::class);
    }

    public function testProcessWithoutDefinition()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(EventsCompilerPass::SERVICE_KEY)
            ->willReturn(false);

        $this->containerBuilder->expects($this->never())->method('findDefinition');
        $this->definition->expects($this->never())->method('addMethodCall');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithCall()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(EventsCompilerPass::SERVICE_KEY)
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('findDefinition')
            ->with(EventsCompilerPass::DISPATCHER_KEY)
            ->willReturn($this->definition);

        $this->definition->expects($this->once())
            ->method('getMethodCalls')
            ->willReturn(
                [
                    [LoadWorkflowNotificationEvents::TRANSIT_EVENT, [EventsCompilerPass::SERVICE_KEY, 'process']]
                ]
            );

        $this->definition->expects($this->never())->method('addMethodCall');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(EventsCompilerPass::SERVICE_KEY)
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('findDefinition')
            ->with(EventsCompilerPass::DISPATCHER_KEY)
            ->willReturn($this->definition);

        $this->definition->expects($this->once())->method('getMethodCalls')->willReturn([]);
        $this->definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'addListenerService',
                [LoadWorkflowNotificationEvents::TRANSIT_EVENT, [EventsCompilerPass::SERVICE_KEY, 'process']]
            );

        $this->compilerPass->process($this->containerBuilder);
    }
}
