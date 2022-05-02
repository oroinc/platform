<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\UserBundle\DependencyInjection\OroUserExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroUserExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadWithDefaults(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroUserExtension();
        $extension->load([], $container);

        $extensionConfig = $container->getExtensionConfig('oro_user');
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'password_min_length' => ['value' => 8, 'scope' => 'app'],
                        'password_lower_case' => ['value' => true, 'scope' => 'app'],
                        'password_upper_case' => ['value' => true, 'scope' => 'app'],
                        'password_numbers' => ['value' => true, 'scope' => 'app'],
                        'password_special_chars' => ['value' => false, 'scope' => 'app'],
                        'send_password_in_invitation_email' => ['value' => false, 'scope' => 'app'],
                        'case_insensitive_email_addresses_enabled' => ['value' => false, 'scope' => 'app'],
                    ]
                ]
            ],
            $extensionConfig
        );

        $this->assertEquals(86400, $container->getParameter('oro_user.reset.ttl'));
    }

    public function testLoad(): void
    {
        $config = [
            'reset' => [
                'ttl' => 1800
            ]
        ];

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroUserExtension();
        $extension->load([$config], $container);

        $this->assertEquals(1800, $container->getParameter('oro_user.reset.ttl'));
    }

    public function testPrepend(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('security', [
            [
                'firewalls' => [
                    'main' => ['main_config'],
                    'first' => ['first_config'],
                    'second' => ['second_config'],
                ]
            ]
        ]);

        $extension = new OroUserExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'firewalls' => [
                        'first' => ['first_config'],
                        'second' => ['second_config'],
                        'main' => ['main_config'],
                    ]
                ]
            ],
            $container->getExtensionConfig('security')
        );
    }
}
