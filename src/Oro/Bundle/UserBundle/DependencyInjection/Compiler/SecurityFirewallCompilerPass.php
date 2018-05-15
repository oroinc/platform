<?php

namespace Oro\Bundle\UserBundle\DependencyInjection\Compiler;

use Escape\WSSEAuthenticationBundle\DependencyInjection\Security\Factory\Factory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiller pass adds the firewall name to the WSSE request listener and authentication provider. This allows
 * to WSSE authentication provider to be executed only at one security firewall.
 */
class SecurityFirewallCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $securityConfigs = $container->getExtensionConfig('security');
        if (empty($securityConfigs[0]['firewalls'])) {
            return;
        }

        $wsseFacroty = new Factory();
        $wsseKey = $wsseFacroty->getKey();
        foreach ($securityConfigs[0]['firewalls'] as $name => $config) {
            if (isset($config[$wsseKey])) {
                $providerId = 'escape_wsse_authentication.provider.' . $name;
                $listenerId = 'escape_wsse_authentication.listener.' . $name;
                $container->getDefinition($providerId)->addMethodCall('setFirewallName', [$name]);
                $container->getDefinition($listenerId)->addMethodCall('setFirewallName', [$name]);
            }
        }
    }
}
