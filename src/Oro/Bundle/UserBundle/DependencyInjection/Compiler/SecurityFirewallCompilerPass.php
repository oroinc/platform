<?php

namespace Oro\Bundle\UserBundle\DependencyInjection\Compiler;

use Escape\WSSEAuthenticationBundle\DependencyInjection\Security\Factory\Factory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
        $listenerDefinition = $container->getDefinition('escape_wsse_authentication.listener');
        $listenerDefinition->addArgument(new Reference('oro_user.token.factory.wsse'));

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
