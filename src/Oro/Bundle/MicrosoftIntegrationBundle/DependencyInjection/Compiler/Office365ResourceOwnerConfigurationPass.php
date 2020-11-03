<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle\DependencyInjection\Compiler;

use Oro\Bundle\MicrosoftIntegrationBundle\OAuth\Office365ResourceOwner;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures OAuth single sign-on authentication resource owner for Microsoft Office 365.
 */
class Office365ResourceOwnerConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('hwi_oauth.resource_owner.office365.class', Office365ResourceOwner::class);
        $container->getDefinition('hwi_oauth.resource_owner.office365')
            ->addMethodCall('setCrypter', [new Reference('oro_security.encoder.default')])
            ->addMethodCall('configureCredentials', [new Reference('oro_config.global')]);
    }
}
