<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\EventsCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EventsCompilerPassTest extends TestCase
{
    private EventsCompilerPass $compiler;

    #[\Override]
    protected function setUp(): void
    {
        $this->compiler = new EventsCompilerPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $notificationManagerDef = $container->register('oro_notification.manager');

        $container->setParameter(
            'oro_notification.events',
            ['my_custom_event_1', 'my_custom_event_2']
        );

        $this->compiler->process($container);

        self::assertEquals(
            [
                'kernel.event_listener' => [
                    ['event' => 'my_custom_event_1', 'method' => 'process'],
                    ['event' => 'my_custom_event_2', 'method' => 'process']
                ]
            ],
            $notificationManagerDef->getTags()
        );
    }

    public function testProcessWhenNoEvents(): void
    {
        $container = new ContainerBuilder();
        $notificationManagerDef = $container->register('oro_notification.manager');

        $container->setParameter('oro_notification.events', []);

        $this->compiler->process($container);

        self::assertSame([], $notificationManagerDef->getTags());
    }
}
