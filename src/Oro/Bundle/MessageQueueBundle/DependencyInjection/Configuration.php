<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection;

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
        $rootNode = $tb->root('oro_message_queue');

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
            ->arrayNode('client')->children()
                ->scalarNode('prefix')->defaultValue('oro.message_queue.client')->end()
                ->scalarNode('router_processor')->defaultNull()->end()
                ->scalarNode('router_destination')->defaultValue('default')->cannotBeEmpty()->end()
                ->scalarNode('default_destination')->defaultValue('default')->cannotBeEmpty()->end()
            ->end()->end()
        ;

        return $tb;
    }
}
