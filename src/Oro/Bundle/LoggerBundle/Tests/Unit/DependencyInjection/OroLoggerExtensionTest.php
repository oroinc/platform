<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LoggerBundle\DependencyInjection\OroLoggerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroLoggerExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroLoggerExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'detailed_logs_level' => ['value' => 'error', 'scope' => 'app'],
                        'detailed_logs_end_timestamp' => ['value' => null, 'scope' => 'app'],
                        'email_notification_recipients' => ['value' => '', 'scope' => 'app'],
                        'email_notification_subject' => ['value' => 'An Error Occurred!', 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_logger')
        );
    }
}
