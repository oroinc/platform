<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Extension;

use Oro\Bundle\SecurityBundle\DependencyInjection\Extension\SecurityExtensionHelper;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class SecurityExtensionHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testMakeFirewallLatestNoFirewall(): void
    {
        $securityConfig = [
            [
                'firewalls' => [
                    'main' => ['main_config'],
                ]
            ]
        ];

        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('security', $securityConfig);

        SecurityExtensionHelper::makeFirewallLatest($container, 'another');

        self::assertEquals($securityConfig, $container->getExtensionConfig('security'));
    }

    public function testMakeFirewallLatest(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('security', [
            [
                'firewalls' => [
                    'main'   => ['main_config'],
                    'first'  => ['first_config'],
                    'second' => ['second_config'],
                ]
            ]
        ]);

        SecurityExtensionHelper::makeFirewallLatest($container, 'main');

        self::assertSame(
            [
                [
                    'firewalls' => [
                        'first'  => ['first_config'],
                        'second' => ['second_config'],
                        'main'   => ['main_config'],
                    ]
                ]
            ],
            $container->getExtensionConfig('security')
        );
    }
}
