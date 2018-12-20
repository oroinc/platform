<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\ApiDoc\LogoutSuccessHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures the success handler for API sandbox logout.
 */
class ApiDocLogoutCompilerPass implements CompilerPassInterface
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

        foreach ($securityConfigs[0]['firewalls'] as $name => $config) {
            if ($this->isLogoutFirewall($config)) {
                $this->configureLogoutHandler($container, $name);
            }
        }
    }

    /**
     * Checks whether a firewall is configured to handle logout
     *
     * @param array $firewallConfig
     *
     * @return bool
     */
    private function isLogoutFirewall(array $firewallConfig): bool
    {
        return
            array_key_exists('logout', $firewallConfig)
            && is_array($firewallConfig['logout'])
            && !empty($firewallConfig['logout']['path']);
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $firewallName
     */
    private function configureLogoutHandler(ContainerBuilder $container, string $firewallName): void
    {
        $listenerId = 'security.logout_listener.' . $firewallName;
        if (!$container->hasDefinition($listenerId)) {
            return;
        }

        // decorate the logout success handler
        $successHandlerId = (string)$container->getDefinition($listenerId)->getArgument(2);
        $successHandlerDecoratorId = 'oro_api.api_doc.' . $successHandlerId;
        $container
            ->register($successHandlerDecoratorId, LogoutSuccessHandler::class)
            ->setArguments([
                new Reference($successHandlerDecoratorId . '.inner'),
                new Reference('oro_api.rest.doc_url_generator')
            ])
            ->setDecoratedService($successHandlerId)
            ->setPublic(false);
    }
}
