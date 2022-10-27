<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ImapBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigurationDefault(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, []);

        self::assertEquals('oro_user_email_origin', $config['user_email_origin_transport']);
        self::assertEquals(
            [
                'resolved' => true,
                'enable_google_imap' =>
                    [
                        'value' => false,
                        'scope' => 'app',
                    ],
                'enable_microsoft_imap' =>
                    [
                        'value' => false,
                        'scope' => 'app',
                    ],
            ],
            $config['settings']
        );
    }

    public function testConfigurationWithCustomUserEmailOriginTransport(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration(
            $configuration,
            ['oro_imap' => ['user_email_origin_transport' => 'sample_transport']]
        );

        self::assertEquals('sample_transport', $config['user_email_origin_transport']);
    }
}
