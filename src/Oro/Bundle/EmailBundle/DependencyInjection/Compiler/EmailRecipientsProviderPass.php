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
        $priorities = [];
        foreach ($taggedServices as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $references[$serviceId] = new Reference($serviceId);
                $priorities[$serviceId] = array_key_exists('priority', $attributes) ? $attributes['priority'] : 0;
            }
        }

        asort($priorities);
        uksort($references, function ($a, $b) use ($priorities) {
            return $priorities[$a] - $priorities[$b];
        });

        $providerDef->addMethodCall('setProviders', [$references]);
    }
}
