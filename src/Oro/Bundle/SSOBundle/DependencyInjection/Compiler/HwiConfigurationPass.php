<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection\Compiler;

use Oro\Bundle\SSOBundle\Security\OAuthAuthenticator;
use Oro\Bundle\SSOBundle\Security\RefreshAccessTokenListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures HWIOAuthBundle services.
 */
class HwiConfigurationPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('security.authenticator.oauth.main')
            ->setClass(OAuthAuthenticator::class)
            ->setArguments(
                $container->getDefinition('security.authenticator.oauth.main')->getArguments()
            )
            ->addMethodCall('setTokenFactory', [new Reference('oro_sso.token.factory.oauth')])
            ->addMethodCall(
                'setOrganizationGuesser',
                [new Reference('oro_security.authentication.organization_guesser')]
            );

        $container->getDefinition('hwi_oauth.context_listener.token_refresher.main')
            ->setClass(RefreshAccessTokenListener::class)
            ->replaceArgument(0, new Reference('security.authenticator.oauth.main'))
        ;
        // remove services that use deleted classes
        $container->removeDefinition('hwi_oauth.authentication.listener.oauth');
        $container->removeDefinition('hwi_oauth.authentication.provider.oauth');
    }
}
