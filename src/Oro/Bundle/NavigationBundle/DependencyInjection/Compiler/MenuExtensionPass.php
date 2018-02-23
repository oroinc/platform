<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MenuExtensionPass implements CompilerPassInterface
{
    const MENU_FACTORY_TAG = 'oro_menu.factory';
    const MENU_EXTENSION_TAG = 'oro_navigation.menu_extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::MENU_FACTORY_TAG)) {
            return;
        }

        $extensions = $container->findTaggedServiceIds(self::MENU_EXTENSION_TAG);
        if (!$extensions) {
            return;
        }

        $serviceDefinition = $container->getDefinition(self::MENU_FACTORY_TAG);
        foreach ($extensions as $id => $tags) {
            foreach ($tags as $attributes) {
                $serviceDefinition->addMethodCall('addExtension', [new Reference($id), $attributes['priority']]);
            }
        }
    }
}
