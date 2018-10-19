<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\HttpBasicFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Creates services for HTTP basic authentication with organization
 */
class OrganizationHttpBasicFactory extends HttpBasicFactory
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $provider = 'oro_security.authentication.provider.username_password_organization.' . $id;
        $container
            ->setDefinition(
                $provider,
                new ChildDefinition('oro_security.authentication.provider.username_password_organization')
            )
            ->replaceArgument(0, new Reference($userProvider))
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

        return array($provider, $listenerId, $entryPointId);
    }

    public function getKey()
    {
        return 'organization-http-basic';
    }
}
