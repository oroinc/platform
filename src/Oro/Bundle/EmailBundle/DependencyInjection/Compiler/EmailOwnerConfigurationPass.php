<?php

namespace Oro\Bundle\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds all email owner providers to an email owner storage.
 */
class EmailOwnerConfigurationPass implements CompilerPassInterface
{
    private const SERVICE_KEY = 'oro_email.email.owner.provider.storage';
    private const TAG         = 'oro_email.owner.provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $storageDefinition = $container->getDefinition(self::SERVICE_KEY);
        $providers = $this->loadProviders($container);
        foreach ($providers as $providerServiceId) {
            $storageDefinition->addMethodCall('addProvider', [new Reference($providerServiceId)]);
        }
    }

    /**
     * Loads services that implement an email owner providers.
     */
    private function loadProviders(ContainerBuilder $container): array
    {
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        $providers = [];
        foreach ($taggedServices as $id => $tagAttributes) {
            $order = PHP_INT_MAX;
            foreach ($tagAttributes as $attributes) {
                if (!empty($attributes['order'])) {
                    $order = (int)$attributes['order'];
                    break;
                }
            }
            $providers[$order][] = $id;
        }
        ksort($providers);

        $providersPlain = [];
        foreach ($providers as $order => $definitions) {
            foreach ($definitions as $definition) {
                $providersPlain[] = $definition;
            }
        }

        return $providersPlain;
    }
}
