<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates services for HTTP basic authentication with organization.
 */
class OrganizationHttpBasicFactory implements AuthenticatorFactoryInterface
{
    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ): string {
        // authenticator
        $authenticatorId = 'oro_security.authentication.authenticator.basic.' . $firewallName;
        $container
            ->setDefinition(
                $authenticatorId,
                new ChildDefinition('oro_security.authentication.authenticator.basic')
            )
            ->replaceArgument(1, $config['realm'])
            ->replaceArgument(2, new Reference($userProviderId));

        return $authenticatorId;
    }

    public function getKey(): string
    {
        return 'organization-http-basic';
    }

    public function addConfiguration(NodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('provider')->end()
            ->scalarNode('realm')->defaultValue('Secured Area')->end()
            ->end();
    }

    public function getPriority(): int
    {
        return -50;
    }
}
