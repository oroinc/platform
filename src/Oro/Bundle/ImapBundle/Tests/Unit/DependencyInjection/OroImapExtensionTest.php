<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ImapBundle\Controller as ImapControllers;
use Oro\Bundle\ImapBundle\DependencyInjection\OroImapExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroImapExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $containerBuilder = new ContainerBuilder();
        $extension = new OroImapExtension();

        $extension->load([], $containerBuilder);

        self::assertEquals(
            'oro_user_email_origin',
            $containerBuilder->getParameter('oro_imap.user_email_origin_transport')
        );

        self::assertTrue($containerBuilder->has(ImapControllers\CheckConnectionController::class));
        self::assertTrue($containerBuilder->has(ImapControllers\ConnectionController::class));
        self::assertTrue($containerBuilder->has(ImapControllers\GmailAccessTokenController::class));
        self::assertTrue($containerBuilder->has(ImapControllers\MicrosoftAccessTokenController::class));
    }

    public function testLoadWithCustomUserEmailOriginTransport(): void
    {
        $containerBuilder = new ContainerBuilder();
        $extension = new OroImapExtension();

        $extension->load(['oro_imap' => ['user_email_origin_transport' => 'sample_transport']], $containerBuilder);

        self::assertEquals(
            'sample_transport',
            $containerBuilder->getParameter('oro_imap.user_email_origin_transport')
        );
    }
}
