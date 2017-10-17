<?php
namespace Oro\Bundle\MessageQueueBundle\DependencyInjection;

use Oro\Component\MessageQueue\DependencyInjection\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @var TransportFactoryInterface[]
     */
    private $factories;

    /**
     * @param TransportFactoryInterface[] $factories
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('oro_message_queue');
        
        $transportChildren = $rootNode->children()
            ->arrayNode('transport')->isRequired()->children();
            
        foreach ($this->factories as $factory) {
            $factory->addConfiguration(
                $transportChildren->arrayNode($factory->getName())
            );
        }

        $rootNode->children()
            ->arrayNode('client')->children()
                ->booleanNode('traceable_producer')->defaultFalse()->end()
                ->scalarNode('prefix')->defaultValue('oro')->end()
                ->scalarNode('router_processor')
                    ->defaultValue('oro_message_queue.client.route_message_processor')
                ->end()
                ->scalarNode('router_destination')->defaultValue('default')->cannotBeEmpty()->end()
                ->scalarNode('default_destination')->defaultValue('default')->cannotBeEmpty()->end()
                ->integerNode('redelivered_delay_time')->min(1)->defaultValue(10)->cannotBeEmpty()->end()
            ->end()->end()
            ->arrayNode('persistent_services')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('persistent_processors')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('security_agnostic_topics')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('security_agnostic_processors')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('consumer')
                ->children()
                    ->integerNode('heartbeat_update_period')
                        ->min(0)
                        ->defaultValue(15)
                        ->info(
                            'Consumer heartbeat update period in minutes. To disable the checks, set this option to 0'
                        )
                    ->end()
                ->end()
            ->end()
            ->arrayNode('job')
                ->children()
                    ->arrayNode('time_before_stale')
                        ->requiresAtLeastOneElement()
                        ->useAttributeAsKey('job_name')
                        ->info('DateTime expression - how long job can stay inactive before its consider old')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();
        return $tb;
    }
}
