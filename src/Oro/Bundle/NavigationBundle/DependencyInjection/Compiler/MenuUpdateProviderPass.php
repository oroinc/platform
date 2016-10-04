<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class MenuUpdateProviderPass implements CompilerPassInterface
{
    const BUILDER_SERVICE_ID = 'oro_navigation.menu_update.builder';

    const MENU_UPDATE_PROVIDER_SERVICE_ID = 'oro_navigation.menu_update_provider.default';
    const OWNERSHIP_PROVIDER_TAG = 'oro_navigation.ownership_provider';

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
        if (!$container->hasDefinition(self::BUILDER_SERVICE_ID)) {
            return;
        }

        $providers = $container->findTaggedServiceIds(self::OWNERSHIP_PROVIDER_TAG);
        if (!$providers) {
            return;
        }

        $builderService = $container->getDefinition(self::BUILDER_SERVICE_ID);

        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                $builderService->addMethodCall(
                    'addProvider',
                    [new Reference($id), $attributes['area'], $attributes['priority']]
                );
            }
        }
    }
}
