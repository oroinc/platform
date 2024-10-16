<?php

namespace Oro\Bundle\MicrosoftIntegrationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures OAuth single sign-on authentication resource owner for Microsoft Office 365.
 */
class Office365ResourceOwnerConfigurationPass implements CompilerPassInterface
{
    public const FACTORY = 'oro_microsoft_integration.resource_owner.factory';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getExtensionConfig('hwi_oauth') as $config) {
            if (isset($config['resource_owners']['office365'])) {
                $office365Config = $config['resource_owners']['office365'];
                unset($office365Config['type']);

                $container->getDefinition('hwi_oauth.resource_owner.office365')
                    ->setArguments([
                        new Reference('oro_security.encoder.default'),
                        new Reference('oro_config.global'),
                        new Reference('http_client'),
                        new Reference('security.http_utils'),
                        new Reference('hwi_oauth.storage.session'),
                        'office365',
                        $office365Config
                    ])
                    ->setFactory([new Reference(self::FACTORY), 'create']);
            }
        }
    }
}
