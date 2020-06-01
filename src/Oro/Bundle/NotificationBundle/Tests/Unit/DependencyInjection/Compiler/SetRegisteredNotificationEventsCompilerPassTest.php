<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\SetRegisteredNotificationEventsCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SetRegisteredNotificationEventsCompilerPassTest extends TestCase
{
    public function testProcess()
    {
        $compilerPass = new SetRegisteredNotificationEventsCompilerPass();

        $eventDispatcherDecorator = new Definition();
        $eventDispatcher = new Definition();
        $eventDispatcher->setMethodCalls(
            [
                [
                    'addListener',
                    [
                        'event.with_callback',
                        function () {
                        },
                    ],
                ],
                ['addListener', ['notification_event.with_string_service_id', ['oro_notification.manager', 'process']]],
                ['addListener', ['event.with_reference', [new Reference('some_service'), 'process']]],
                [
                    'addListener',
                    ['notification_event.with_reference', [new Reference('oro_notification.manager'), 'process']],
                ],
                [
                    'addListener',
                    [
                        'event.with_service_closure_argument',
                        [new ServiceClosureArgument(new Reference('some_service')), 'process'],
                    ],
                ],
                [
                    'addListener',
                    [
                        'notification_event.with_service_closure_argument',
                        [new ServiceClosureArgument(new Reference('oro_notification.manager')), 'process'],
                    ],
                ],
            ]
        );

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects($this->exactly(2))
            ->method('findDefinition')
            ->willReturnMap(
                [
                    ['oro_notification.event_dispatcher_decorator', $eventDispatcherDecorator],
                    ['event_dispatcher', $eventDispatcher],
                ]
            );

        $compilerPass->process($containerBuilder);

        $this->assertEquals(
            [
                'setRegisteredNotificationEvents',
                [
                    [
                        'notification_event.with_string_service_id',
                        'notification_event.with_reference',
                        'notification_event.with_service_closure_argument',
                    ],
                ],
            ],
            $eventDispatcherDecorator->getMethodCalls()[0]
        );
    }
}
