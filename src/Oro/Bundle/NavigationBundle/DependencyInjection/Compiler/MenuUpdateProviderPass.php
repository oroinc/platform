<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class MenuUpdateProviderPass implements CompilerPassInterface
{
    const BUILDER_SERVICE_ID = 'oro_navigation.menu_update.builder';
    const UPDATE_PROVIDER_TAG = 'oro_navigation.menu_update_provider';

    const MENU_UPDATE_PROVIDER_SERVICE_ID = 'oro_navigation.menu_update_provider.default';
    const SCOPE_PROVIDER_TAG = 'oro_navigation.ownership_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->processUpdateProviders($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function processUpdateProviders(ContainerBuilder $container)
    {
        $ownershipProviders = $this->collectOwnershipProvidersByArea($container);

        if (!$container->hasDefinition(self::BUILDER_SERVICE_ID)) {
            return;
        }

        $providers = $container->findTaggedServiceIds(self::UPDATE_PROVIDER_TAG);
        if (!$providers) {
            return;
        }

        $builderService = $container->getDefinition(self::BUILDER_SERVICE_ID);

        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                $updateProviderService = $container->getDefinition($id);
                if (isset($ownershipProviders[$attributes['area']])) {
                    foreach ($ownershipProviders[$attributes['area']] as $priority => $ownershipProvider) {
                        $updateProviderService->addMethodCall('addOwnershipProvider', [$ownershipProvider, $priority]);
                    }
                }
                $builderService->addMethodCall('addProvider', [$attributes['area'], new Reference($id)]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function collectOwnershipProvidersByArea(ContainerBuilder $container)
    {
        $providersByArea = [];
        if (!$container->hasDefinition(self::MENU_UPDATE_PROVIDER_SERVICE_ID)) {
            return [];
        }

        $providers = $container->findTaggedServiceIds(self::SCOPE_PROVIDER_TAG);
        if (!$providers) {
            return [];
        }

        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                $providersByArea[$attributes['area']][$attributes['priority']] = new Reference($id);
            }
        }

        return $providersByArea;
    }
}
