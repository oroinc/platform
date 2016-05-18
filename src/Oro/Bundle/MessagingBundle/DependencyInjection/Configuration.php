<?php
namespace Oro\Bundle\MessagingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('oro_messaging');

        $rootNode->children()
            ->arrayNode('transport')->isRequired()->children()
                ->scalarNode('default')->isRequired()->cannotBeEmpty()->end()
                ->booleanNode('null')->defaultFalse()->end()
                ->arrayNode('amqp')->children()
                    ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('port')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('vhost')->isRequired()->cannotBeEmpty()->end()
                ->end()->end()
            ->end()->end()
            ->arrayNode('zeroconfig')->children()
                ->scalarNode('prefix')->defaultValue('zeroconfig')->end()
                ->scalarNode('router_topic')->defaultValue('router')->cannotBeEmpty()->end()
                ->scalarNode('router_queue')->defaultValue('router')->cannotBeEmpty()->end()
                ->scalarNode('queue_topic')->defaultValue('queue')->cannotBeEmpty()->end()
                ->scalarNode('default_queue_queue')->defaultValue('queue')->cannotBeEmpty()->end()
            ->end()->end()
        ;

        return $tb;
    }
}
