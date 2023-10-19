<?php

namespace Oro\Bundle\GoogleIntegrationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures OAuth single sign-on authentication resource owner for Google.
 */
class GoogleResourceOwnerConfigurationPass implements CompilerPassInterface
{
    public const FACTORY = 'oro_google_integration.resource_owner.factory';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getExtensionConfig('hwi_oauth') as $config) {
            if (isset($config['resource_owners']['google'])) {
                $googleConfig = $config['resource_owners']['google'];
                unset($googleConfig['type']);

                $container->getDefinition('hwi_oauth.resource_owner.google')
                    ->setArguments([
                        new Reference('oro_security.encoder.default'),
                        new Reference('oro_config.global'),
                        new Reference('http_client'),
                        new Reference('security.http_utils'),
                        new Reference('hwi_oauth.storage.session'),
                        'google',
                        $googleConfig
                    ])
                    ->setFactory([new Reference(self::FACTORY), 'create']);
            }
        }
    }
}
