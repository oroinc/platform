<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Extension;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

/**
 * Provides static methods to manage configuration of the "security" extension.
 */
class SecurityExtensionHelper
{
    /**
     * Moves the specified firewall configuration to the latest position.
     */
    public static function makeFirewallLatest(ExtendedContainerBuilder $container, string $firewallName): void
    {
        $securityConfig = $container->getExtensionConfig('security');
        if (!isset($securityConfig[0]['firewalls'][$firewallName])) {
            return;
        }

        $mainFirewall = $securityConfig[0]['firewalls'][$firewallName];
        unset($securityConfig[0]['firewalls'][$firewallName]);
        $securityConfig[0]['firewalls'][$firewallName] = $mainFirewall;

        $container->setExtensionConfig('security', $securityConfig);
    }
}
