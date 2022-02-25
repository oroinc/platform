<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all ORM query hints.
 */
class QueryHintResolverPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $tagName = 'oro_entity.query_hint';
        $walkers = [];
        $providers = [];
        $aliases = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (isset($attributes['tree_walker'])) {
                    $walkerClass = $attributes['tree_walker'];
                    $output = false;
                } elseif (isset($attributes['output_walker'])) {
                    $walkerClass = $attributes['output_walker'];
                    $output = true;
                } else {
                    throw new InvalidArgumentException(sprintf(
                        'The "%s" tag should have either "tree_walker" or "output_walker" attribute. Service: "%s".',
                        $tagName,
                        $id
                    ));
                }
                $hint = $this->getRequiredAttribute($attributes, 'hint', $id, $tagName);
                $providerId = $this->getAttribute($attributes, 'walker_hint_provider');
                $walkers[$hint] = [
                    'class'         => $walkerClass,
                    'output'        => $output,
                    'hint_provider' => $providerId
                ];
                if ($providerId) {
                    $providers[$providerId] = new Reference($providerId);
                }
                $alias = $this->getAttribute($attributes, 'alias');
                if ($alias) {
                    $aliases[$alias] = $hint;
                }
            }
        }

        $container->getDefinition('oro_entity.query_hint_resolver')
            ->setArgument('$walkers', $walkers)
            ->setArgument('$walkerHintProviders', ServiceLocatorTagPass::register($container, $providers))
            ->setArgument('$aliases', $aliases);
    }
}
