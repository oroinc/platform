<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection\Extension;

use Oro\Bundle\SecurityBundle\DependencyInjection\Extension\SecurityExtensionHelper;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class SecurityExtensionHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testMakeFirewallLatestNoFirewall()
    {
        $securityConfig = [
            'firewalls' => [
                'not_main' => ['not_main_config'],
            ]
        ];

        $containerBuilder = $this->getContainerBuilder([$securityConfig]);
        $containerBuilder->expects($this->never())
            ->method('setExtensionConfig');

        SecurityExtensionHelper::makeFirewallLatest($containerBuilder, 'latest_firewall');
    }

    public function testMakeFirewallLatest()
    {
        $inputSecurityConfig = [
            'firewalls' => [
                'latest_firewall' => ['main_config'],
                'first' => ['first_config'],
                'second' => ['second_config'],
            ]
        ];
        $expectedSecurityConfig = [
            'firewalls' => [
                'first' => ['first_config'],
                'second' => ['second_config'],
                'latest_firewall' => ['main_config'],
            ]
        ];

        $containerBuilder = $this->getContainerBuilder([$inputSecurityConfig]);
        $containerBuilder->expects($this->once())
            ->method('setExtensionConfig')
            ->with('security', [$expectedSecurityConfig]);

        SecurityExtensionHelper::makeFirewallLatest($containerBuilder, 'latest_firewall');
    }

    /**
     * @param array $securityConfig
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtendedContainerBuilder
     */
    protected function getContainerBuilder(array $securityConfig)
    {
        $containerBuilder = $this->getMockBuilder('Oro\Component\DependencyInjection\ExtendedContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('getExtensionConfig')
            ->with('security')
            ->willReturn($securityConfig);

        return $containerBuilder;
    }
}
