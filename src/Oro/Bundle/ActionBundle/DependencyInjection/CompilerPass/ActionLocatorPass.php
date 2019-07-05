<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all actions inside service locator.
 */
class ActionLocatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $actions = [];

        $ids = array_keys($container->findTaggedServiceIds('oro_action.action', true));
        foreach ($ids as $id) {
            $actions[$id] = new Reference($id);
        }

        $container->getDefinition('oro_action.action_locator')
            ->replaceArgument(0, $actions);
    }
}
