<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EmailRecipientsProviderPass implements CompilerPassInterface
{
    const SERVICE_KEY = 'oro_email.email_recipients.provider';
    const TAG = 'oro_email.recipients_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE_KEY)) {
            return;
        }

        $providerDef = $container->getDefinition(self::SERVICE_KEY);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        $references = [];
        foreach ($taggedServices as $serviceId => $tagAttributes) {
            $references[] = new Reference($serviceId);
        }

        $providerDef->addMethodCall('setProviders', [$references]);
    }
}
