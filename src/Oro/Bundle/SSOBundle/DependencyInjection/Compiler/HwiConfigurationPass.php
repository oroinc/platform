<?php

namespace Oro\Bundle\SSOBundle\DependencyInjection\Compiler;

use Oro\Bundle\SSOBundle\Security\OAuthProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures HWIOAuthBundle services.
 */
class HwiConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('hwi_oauth.authentication.provider.oauth')
            ->setClass(OAuthProvider::class)
            ->addMethodCall('setTokenFactory', [new Reference('oro_sso.token.factory.oauth')])
            ->addMethodCall(
                'setOrganizationGuesser',
                [new Reference('oro_security.authentication.organization_guesser')]
            );
    }
}
