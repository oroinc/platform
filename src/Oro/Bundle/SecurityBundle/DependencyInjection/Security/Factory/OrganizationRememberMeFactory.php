<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Oro\Bundle\SecurityBundle\Authentication\Authenticator\OrganizationRememberMeAuthenticationAuthenticator;
use Oro\Bundle\SecurityBundle\Authentication\Listener\RememberMeListener;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FirewallListenerFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\RememberMeFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures services for "Remember Me" login functionality.
 */
class OrganizationRememberMeFactory extends RememberMeFactory implements FirewallListenerFactoryInterface
{
    public function __construct()
    {
        $this->options['csrf_protected_mode'] = false;
    }

    #[\Override]
    public function getKey(): string
    {
        return 'organization-remember-me';
    }

    #[\Override]
    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ): string {
        parent::createAuthenticator($container, $firewallName, $config, $userProviderId);
        $defRememberMeAuthenticator = $container->getDefinition('security.authenticator.remember_me');

        $authenticatorId = 'oro_security.authentication.authenticator.organization_rememberme.' . $firewallName;
        $container
            ->register($authenticatorId, OrganizationRememberMeAuthenticationAuthenticator::class)
            ->setArgument(
                '$rememberMeHandler',
                new Reference('security.authenticator.remember_me_handler.' . $firewallName)
            )
            ->setArgument('$secret', $config['secret'])
            ->setArgument('$tokenStorage', $defRememberMeAuthenticator->getArgument(2))
            ->setArgument('$cookieName', $config['name'])
            ->setArgument('$logger', $defRememberMeAuthenticator->getArgument(4))
            ->addMethodCall(
                'setTokenFactory',
                [new Reference('oro_security.token.factory.organization_rememberme')]
            )
            ->addMethodCall(
                'setOrganizationGuesser',
                [new Reference('oro_security.authentication.organization_guesser')]
            );

        return $authenticatorId;
    }

    #[\Override]
    public function createListeners(ContainerBuilder $container, string $firewallName, array $config): array
    {
        $listenerId = 'oro_security.authentication.listener.rememberme.' . $firewallName;
        $authenticatorId = 'oro_security.authentication.authenticator.organization_rememberme.' . $firewallName;
        $container
            ->register($listenerId, RememberMeListener::class)
            ->setArguments([
                new Reference($authenticatorId),
                new Reference('oro_security.csrf_protected_request_helper'),
                $config['csrf_protected_mode']
            ]);

        return [$listenerId];
    }
}
