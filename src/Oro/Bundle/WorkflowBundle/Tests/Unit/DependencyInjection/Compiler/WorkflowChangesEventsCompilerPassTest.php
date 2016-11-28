<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\WorkflowChangesEventsCompilerPass;

class WorkflowChangesEventsCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $containerBuilderMock;

    /** @var WorkflowChangesEventsCompilerPass */
    protected $pass;

    protected function setUp()
    {
        $this->containerBuilderMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->pass = new WorkflowChangesEventsCompilerPass();
    }

    public function testProcessIgnoreWithoutService()
    {
        $this->containerBuilderMock->expects($this->once())->method('hasDefinition')->willReturn(false);

        $this->containerBuilderMock->expects($this->never())
            ->method('getDefinition')->with($this->anything());

        $this->pass->process($this->containerBuilderMock);
    }

    public function testProcessSubscribers()
    {
        $this->containerBuilderMock->expects($this->at(0))->method('hasDefinition')->willReturn(true);

        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()->getMock();

        $this->containerBuilderMock->expects($this->at(1))
            ->method('getDefinition')->with('oro_workflow.changes.event.dispatcher')->willReturn($definition);

        //no listeners
        $this->containerBuilderMock->expects($this->at(2))
            ->method('findTaggedServiceIds')
            ->with('oro_workflow.changes.listener')->willReturn([]);

        $definition->expects($this->never())->method('addListener')->with($this->anything());

        //subscribers
        $this->containerBuilderMock->expects($this->at(3))
            ->method('findTaggedServiceIds')
            ->with('oro_workflow.changes.subscriber')->willReturn(['service' => ['tagdata']]);

        $definition->expects($this->once())->method('addMethodCall')->with('addSubscriber', [new Reference('service')]);

        $this->pass->process($this->containerBuilderMock);
    }

    public function testProcessListeners()
    {
        $this->containerBuilderMock->expects($this->at(0))->method('hasDefinition')->willReturn(true);

        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()->getMock();

        $this->containerBuilderMock->expects($this->at(1))
            ->method('getDefinition')->with('oro_workflow.changes.event.dispatcher')->willReturn($definition);

        //no listeners
        $this->containerBuilderMock->expects($this->at(2))
            ->method('findTaggedServiceIds')
            ->with('oro_workflow.changes.listener')->willReturn(
                [
                    'service1' => [
                        ['event' => 'event1', 'method' => 'process1', 'priority' => 42],
                        ['event' => 'event2', 'method' => 'process2']
                    ],
                    'service2' => [['event' => 'event3', 'method' => 'process3']],
                ]
            );

        $definition->expects($this->at(0))->method('addMethodCall')->with(
            'addListener',
            ['event1', [new Reference('service1'), 'process1'], 42]
        );

        $definition->expects($this->at(1))->method('addMethodCall')->with(
            'addListener',
            ['event2', [new Reference('service1'), 'process2'], 0]
        );

        $definition->expects($this->at(2))->method('addMethodCall')->with(
            'addListener',
            ['event3', [new Reference('service2'), 'process3'], 0]
        );

        //no subscribers
        $this->containerBuilderMock->expects($this->at(3))
            ->method('findTaggedServiceIds')
            ->with('oro_workflow.changes.subscriber')->willReturn([]);

        $definition->expects($this->never())->method('addSubscriber');

        $this->pass->process($this->containerBuilderMock);
    }

    public function testProcessListenersWithoutMethod()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Service "service1" must define the "method" attribute on "oro_workflow.changes.listener" tags.'
        );

        $this->containerBuilderMock->expects($this->once())->method('hasDefinition')->willReturn(true);
        $this->containerBuilderMock->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(WorkflowChangesEventsCompilerPass::CHANGES_LISTENER_TAG)
            ->willReturn(['service1' => [['event' => 'event1']]]);

        $this->pass->process($this->containerBuilderMock);
    }

    public function testProcessListenerEventException()
    {
        $this->containerBuilderMock->expects($this->at(0))->method('hasDefinition')->willReturn(true);

        $this->containerBuilderMock->expects($this->at(1))
            ->method('getDefinition')->with('oro_workflow.changes.event.dispatcher');

        $this->containerBuilderMock->expects($this->at(2))
            ->method('findTaggedServiceIds')
            ->with('oro_workflow.changes.listener')->willReturn(
                [
                    'service1' => [
                        ['priority' => 42],
                    ]
                ]
            );

        $this->setExpectedException(
            'InvalidArgumentException',
            'An "event" attribute for tag `oro_workflow.changes.listener` in service `service1` must be defined'
        );
        $this->pass->process($this->containerBuilderMock);
    }
}
