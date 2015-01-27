<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_LAYOUT_TWIG_RESOURCE = 'OroLayoutBundle:Layout:div_layout.html.twig';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_layout');
        $rootNode
            ->children()
                ->arrayNode('twig')
                    ->canBeDisabled()
                    ->fixXmlConfig('resource')
                    ->children()
                        ->arrayNode('resources')
                            ->addDefaultChildrenIfNoneSet()
                            ->prototype('scalar')->defaultValue(self::DEFAULT_LAYOUT_TWIG_RESOURCE)->end()
                            ->example(['MyBundle:Layout:blocks.html.twig'])
                            ->validate()
                                ->ifTrue(
                                    function ($v) {
                                        return !in_array(self::DEFAULT_LAYOUT_TWIG_RESOURCE, $v);
                                    }
                                )
                                ->then(function ($v) {
                                    return array_merge([self::DEFAULT_LAYOUT_TWIG_RESOURCE], $v);
                                })
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
