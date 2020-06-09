<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\EventsCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class EventsCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        $compilerPass = new EventsCompilerPass();
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $definition = $this->createMock(Definition::class);

        $containerBuilder->expects($this->once())
            ->method('findDefinition')
            ->with('oro_notification.manager')
            ->willReturn($definition);

        $containerBuilder->expects($this->once())
            ->method('getParameter')
            ->with('oro_notification.events')
            ->willReturn(['my_custom_event_1', 'my_custom_event_2']);

        $definition->expects($this->exactly(2))
            ->method('addTag')
            ->withConsecutive(
                [
                    'kernel.event_listener',
                    [
                        'event' => 'my_custom_event_1',
                        'method' => 'process',
                    ]
                ],
                [
                    'kernel.event_listener',
                    [
                        'event' => 'my_custom_event_2',
                        'method' => 'process',
                    ]
                ]
            );

        $compilerPass->process($containerBuilder);
    }
}
