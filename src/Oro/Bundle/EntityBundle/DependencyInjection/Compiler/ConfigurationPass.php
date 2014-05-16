<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationPass implements CompilerPassInterface
{
    const PROVIDER_STORAGE_TAG   = 'oro_entiy.entityfield_chain_provider';
    const PROVIDER_EXTENSION_TAG = 'oro_entiy.entityfield_provider_extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
       $this->addEntityFieldProviderExtensions($container);
    }

    /**
     * Add entity provider extensions to extension storage
     * that will be injected in providers
     *
     * @param ContainerBuilder $container
     */
    protected function addEntityFieldProviderExtensions(ContainerBuilder $container)
    {
        $providerExtRef = $container->getDefinition(self::PROVIDER_STORAGE_TAG);

        $providerExtensions = $container->findTaggedServiceIds(self::PROVIDER_EXTENSION_TAG);
        foreach ($providerExtensions as $extension) {
            $providerExtRef->addMethodCall('addExtension', [$extension]);
        }
    }
}
