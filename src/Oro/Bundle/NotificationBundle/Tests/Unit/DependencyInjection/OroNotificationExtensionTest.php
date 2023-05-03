<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NotificationBundle\DependencyInjection\OroNotificationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroNotificationExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroNotificationExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'email_notification_sender_email' => [
                            'value' => sprintf('no-reply@%s.example', gethostname()),
                            'scope' => 'app'
                        ],
                        'email_notification_sender_name' => ['value' => 'Oro', 'scope' => 'app'],
                        'mass_notification_template' => ['value' => 'system_maintenance', 'scope' => 'app'],
                        'mass_notification_recipients' => ['value' => '', 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_notification')
        );
    }
}
