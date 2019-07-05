<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all conditions inside service locator.
 */
class ConditionLocatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $conditions = [];

        $ids = array_keys($container->findTaggedServiceIds('oro_action.condition', true));
        foreach ($ids as $id) {
            $conditions[$id] = new Reference($id);
        }

        $container->getDefinition('oro_action.condition_locator')
            ->replaceArgument(0, $conditions);
    }
}
