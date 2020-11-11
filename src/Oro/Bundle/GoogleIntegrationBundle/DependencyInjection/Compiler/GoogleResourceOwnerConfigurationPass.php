<?php

namespace Oro\Bundle\GoogleIntegrationBundle\DependencyInjection\Compiler;

use Oro\Bundle\GoogleIntegrationBundle\OAuth\GoogleResourceOwner;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures OAuth single sign-on authentication resource owner for Google.
 */
class GoogleResourceOwnerConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('hwi_oauth.resource_owner.google.class', GoogleResourceOwner::class);
        $container->getDefinition('hwi_oauth.resource_owner.google')
            ->addMethodCall('setCrypter', [new Reference('oro_security.encoder.default')])
            ->addMethodCall('configureCredentials', [new Reference('oro_config.global')]);
    }
}
