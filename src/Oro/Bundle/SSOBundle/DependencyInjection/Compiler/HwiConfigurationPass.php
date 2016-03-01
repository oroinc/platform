<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class HwiConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resourceOwners = [
            'google',
        ];

        foreach ($resourceOwners as $owner) {
            $id = sprintf('hwi_oauth.resource_owner.%s', $owner);
            if (!$container->hasDefinition($id)) {
                continue;
            }

            $definition = $container->findDefinition($id);
            $definition->addMethodCall('configureCredentials', [
                new Reference('oro_config.global'),
            ]);
        }

        if ($container->hasDefinition('hwi_oauth.authentication.provider.oauth')) {
            $definition = $container->getDefinition('hwi_oauth.authentication.provider.oauth');
            $definition->addMethodCall('setTokenFactory', [new Reference('oro_sso.token.factory.oauth')]);
        }
    }
}
