<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\UserBundle\DependencyInjection\OroUserExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroUserExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroUserExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
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
            $container->getExtensionConfig('oro_user')
        );

        $this->assertSame(86400, $container->getParameter('oro_user.reset.ttl'));
        $this->assertSame([], $container->getParameter('oro_user.privileges'));
        $this->assertSame([], $container->getParameter('oro_user.login_sources'));
    }

    public function testLoadWithCustomConfigs(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $configs = [
            ['reset' => ['ttl' => 1800]]
        ];

        $extension = new OroUserExtension();
        $extension->load($configs, $container);

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
