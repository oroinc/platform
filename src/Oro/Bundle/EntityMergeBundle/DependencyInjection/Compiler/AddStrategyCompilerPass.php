<?php

namespace Oro\Bundle\EntityMergeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddStrategyCompilerPass implements CompilerPassInterface
{
    const STRATEGY_TAG = 'oro_entity_merge.strategy';
    const DELEGATE_STRATEGY_SERVICE = 'oro_entity_merge.strategy.delegate';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $normalizerDefinition = $container->getDefinition(self::DELEGATE_STRATEGY_SERVICE);
        foreach ($container->findTaggedServiceIds(self::STRATEGY_TAG) as $id => $attributes) {
            $normalizerDefinition->addMethodCall('add', array(new Reference($id)));
        }
    }
}
