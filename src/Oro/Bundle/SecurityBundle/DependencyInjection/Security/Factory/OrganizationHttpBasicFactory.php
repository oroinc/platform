<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates services for HTTP basic authentication with organization
 */
class OrganizationHttpBasicFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint): array
    {
        $provider = 'oro_security.authentication.provider.username_password_organization.' . $id;
        $container
            ->setDefinition(
                $provider,
                new ChildDefinition('oro_security.authentication.provider.username_password_organization')
            )
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(1, new Reference('security.user_checker.' . $id))
            ->replaceArgument(2, $id);

        // entry point
        $entryPointId = $this->createEntryPoint($container, $id, $config, $defaultEntryPoint);

        // listener
        $listenerId = 'oro_security.authentication.listener.basic.' . $id;
        $listener = $container->setDefinition(
            $listenerId,
            new ChildDefinition('oro_security.authentication.listener.basic')
        );
        $listener->replaceArgument(2, $id);
        $listener->replaceArgument(3, new Reference($entryPointId));

        return [$provider, $listenerId, $entryPointId];
    }

    public function getKey(): string
    {
        return 'organization-http-basic';
    }

    public function getPosition(): string
    {
        return 'http';
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->children()
            ->scalarNode('provider')->end()
            ->scalarNode('realm')->defaultValue('Secured Area')->end()
            ->end();
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPoint): string
    {
        if (null !== $defaultEntryPoint) {
            return $defaultEntryPoint;
        }

        $entryPointId = 'security.authentication.basic_entry_point.' . $id;
        $container
            ->setDefinition($entryPointId, new ChildDefinition('security.authentication.basic_entry_point'))
            ->addArgument($config['realm']);

        return $entryPointId;
    }
}
