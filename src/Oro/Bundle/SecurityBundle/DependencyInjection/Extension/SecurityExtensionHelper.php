<?php
namespace Oro\Bundle\SecurityBundle\DependencyInjection\Extension;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroContainerBuilder;

class SecurityExtensionHelper
{
    /**
     * Move specified firewall to the latest position, it should be done for the most general firewalls
     *
     * @param OroContainerBuilder $container
     * @param string $firewallName
     */
    public static function makeFirewallLatest(OroContainerBuilder $container, $firewallName)
    {
        $securityConfig = $container->getExtensionConfig('security');
        if (!isset($securityConfig[0]['firewalls'][$firewallName])) {
            return;
        }

        $mainFirewall = $securityConfig[0]['firewalls'][$firewallName];
        unset($securityConfig[0]['firewalls'][$firewallName]);
        $securityConfig[0]['firewalls'][$firewallName] = $mainFirewall;
        /** @var OroContainerBuilder $container */
        $container->setExtensionConfig('security', $securityConfig);
    }
}
