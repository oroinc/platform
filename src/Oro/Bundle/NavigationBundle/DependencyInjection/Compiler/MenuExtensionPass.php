<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all extensions for menu factory.
 */
class MenuExtensionPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $extensions = $container->findTaggedServiceIds('oro_navigation.menu_extension');
        if (!$extensions) {
            return;
        }

        $factoryDef = $container->getDefinition('oro_menu.factory');
        foreach ($extensions as $id => $tags) {
            foreach ($tags as $attributes) {
                $factoryDef->addMethodCall(
                    'addExtension',
                    [new Reference($id), $this->getPriorityAttribute($attributes)]
                );
            }
        }
    }
}
