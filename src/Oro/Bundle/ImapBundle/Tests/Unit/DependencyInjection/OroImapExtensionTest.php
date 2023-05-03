<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ImapBundle\DependencyInjection\OroImapExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroImapExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroImapExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'enable_google_imap' => ['value' => false, 'scope' => 'app'],
                        'enable_microsoft_imap' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_imap')
        );

        self::assertEquals(
            'oro_user_email_origin',
            $container->getParameter('oro_imap.user_email_origin_transport')
        );
    }

    public function testLoadWithCustomConfigs(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroImapExtension();
        $extension->load(['oro_imap' => ['user_email_origin_transport' => 'sample_transport']], $container);

        self::assertEquals(
            'sample_transport',
            $container->getParameter('oro_imap.user_email_origin_transport')
        );
    }
}
