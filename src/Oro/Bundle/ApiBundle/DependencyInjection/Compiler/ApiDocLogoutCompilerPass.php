<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures the success handler for API sandbox logout.
 */
class ApiDocLogoutCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $securityConfigs = $container->getExtensionConfig('security');
        if (empty($securityConfigs[0]['firewalls'])) {
            return;
        }

        foreach ($securityConfigs[0]['firewalls'] as $name => $config) {
            if ($this->isLogoutFirewall($config)) {
                $this->addLogoutListener($container, $name);
            }
        }
    }

    /**
     * Checks whether a firewall is configured to handle logout
     */
    private function isLogoutFirewall(array $firewallConfig): bool
    {
        return
            \array_key_exists('logout', $firewallConfig)
            && \is_array($firewallConfig['logout'])
            && !empty($firewallConfig['logout']['path']);
    }

    private function addLogoutListener(ContainerBuilder $container, string $firewallName): void
    {
        $firewallEventDispatcherId = 'security.event_dispatcher.' . $firewallName;
        $logoutListenerId = 'oro_api.api_doc.security.logout_listener.' . $firewallName;
        $container
            ->setDefinition($logoutListenerId, new ChildDefinition('oro_api.security.event_listener.logout_listener'))
            ->addTag('kernel.event_subscriber', ['dispatcher' => $firewallEventDispatcherId]);
    }
}
