<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Handles from login definition
 */
class OrganizationFormLoginFactory extends FormLoginFactory
{
    public function __construct()
    {
        $this->addOption('organization_parameter', '_organization');
        parent::__construct();
    }

    public function getKey(): string
    {
        return 'organization-form-login';
    }

    protected function createAuthProvider(
        ContainerBuilder $container,
        string $id,
        array $config,
        string $userProviderId
    ): string {
        $provider = 'oro_security.authentication.provider.username_password_organization.' . $id;
        $container
            ->setDefinition(
                $provider,
                new ChildDefinition('oro_security.authentication.provider.username_password_organization')
            )
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference('security.user_checker.' . $id))
            ->replaceArgument(2, $id);

        return $provider;
    }
}
