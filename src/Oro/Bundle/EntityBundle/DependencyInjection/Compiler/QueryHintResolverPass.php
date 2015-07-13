<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class QueryHintResolverPass implements CompilerPassInterface
{
    const RESOLVER_SERVICE = 'oro_entity.query_hint_resolver';
    const TAG_NAME = 'oro_entity.query_hint';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::RESOLVER_SERVICE)) {
            return;
        }

        $resolverDef = $container->getDefinition(self::RESOLVER_SERVICE);
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $attributes) {
            foreach ($attributes as $attr) {
                if (isset($attr['tree_walker'])) {
                    $resolverDef->addMethodCall(
                        'addTreeWalker',
                        [
                            $attr['hint'],
                            $attr['tree_walker'],
                            isset($attr['walker_hint_provider']) ? new Reference($attr['walker_hint_provider']) : null,
                            isset($attr['alias']) ? $attr['alias'] : null
                        ]
                    );
                } elseif (isset($attr['output_walker'])) {
                    $resolverDef->addMethodCall(
                        'addOutputWalker',
                        [
                            $attr['hint'],
                            $attr['output_walker'],
                            isset($attr['walker_hint_provider']) ? new Reference($attr['walker_hint_provider']) : null,
                            isset($attr['alias']) ? $attr['alias'] : null
                        ]
                    );
                }
            }
        }
    }
}
