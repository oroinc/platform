<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configure HWIOAuthBundle services.
 */
class HwiConfigurationPass implements CompilerPassInterface
{
    private const RESOURCE_OWNERS = ['google', 'office365'];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach (self::RESOURCE_OWNERS as $owner) {
            $id = sprintf('hwi_oauth.resource_owner.%s', $owner);
            if ($container->hasDefinition($id)) {
                $definition = $container->findDefinition($id);
                $definition->addMethodCall('setCrypter', [new Reference('oro_security.encoder.default')]);
                $definition->addMethodCall('configureCredentials', [new Reference('oro_config.global')]);
            }
        }

        if ($container->hasDefinition('hwi_oauth.authentication.provider.oauth')) {
            $container->getDefinition('hwi_oauth.authentication.provider.oauth')
                ->addMethodCall('setTokenFactory', [new Reference('oro_sso.token.factory.oauth')])
                ->addMethodCall(
                    'setOrganizationGuesser',
                    [new Reference('oro_security.authentication.organization_guesser')]
                );
        }
    }
}
