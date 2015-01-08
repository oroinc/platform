<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class OrganizationFormLoginFactory extends FormLoginFactory
{
    public function __construct()
    {
        $this->addOption('organization_parameter', '_organization');
        parent::__construct();
    }

    public function getKey()
    {
        return 'organization-form-login';
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $provider = 'oro_security.authentication.provider.username_password_organization.' . $id;
        $container
            ->setDefinition(
                $provider,
                new DefinitionDecorator('oro_security.authentication.provider.username_password_organization')
            )
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(2, $id);

        return $provider;
    }
}
