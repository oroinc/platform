<?php
namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class MenuUpdateProviderPass implements CompilerPassInterface
{
    const BUILDER_SERVICE_ID = 'oro_navigation.menu_update_builder';
    const TAG = 'oro_navigation.menu_update_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::BUILDER_SERVICE_ID)) {
            return;
        }

        $providers = $container->findTaggedServiceIds(self::TAG);
        if (!$providers) {
            return;
        }

        $service = $container->getDefinition(self::BUILDER_SERVICE_ID);

        foreach ($providers as $id => $tags) {
            foreach ($tags as $attributes) {
                $service->addMethodCall('addProvider', [$attributes['area'], new Reference($id)]);
            }
        }
    }
}
