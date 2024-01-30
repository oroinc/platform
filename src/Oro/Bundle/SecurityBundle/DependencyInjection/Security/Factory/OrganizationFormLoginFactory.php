<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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

    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ): string {
        if (isset($config['csrf_token_generator'])) {
            throw new InvalidConfigurationException(
                'The "csrf_token_generator" option of "organization-form-login" is only available when 
                "security.enable_authenticator_manager" is set to "false", use "enable_csrf" instead.'
            );
        }

        $authenticatorId = 'oro_security.authentication.authenticator.organization_form_login.' . $firewallName;
        $options = array_intersect_key($config, $this->options);
        $authenticator = $container
            ->setDefinition(
                $authenticatorId,
                new ChildDefinition('oro_security.authentication.authenticator.organization_form_login')
            )
            ->replaceArgument(1, new Reference($userProviderId))
            ->replaceArgument(
                2,
                new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config))
            )
            ->replaceArgument(
                3,
                new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config))
            )
            ->replaceArgument(4, $options)
            ->replaceArgument(5, $firewallName);

        if ($options['use_forward'] ?? false) {
            $authenticator->addMethodCall('setHttpKernel', [new Reference('http_kernel')]);
        }

        return $authenticatorId;
    }
}
