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
                ->scalarNode('default_topic')->defaultValue('default')->cannotBeEmpty()->end()
                ->integerNode('redelivered_delay_time')->min(1)->defaultValue(10)->end()
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
            ->arrayNode('time_before_stale')
                ->example("
                time_before_stale:
                    default: X
                    jobs:
                        some_job_type_name: Y
                ")
                ->children()
                    ->integerNode('default')
                        ->min(-1)
                        ->info('Number of seconds of inactivity to qualify job as stale. 
                        If this attribute is not set or it is set to -1 jobs will never be qualified as stale. 
                        It means that if a unique Job is not properly removed after finish it will be blocking other 
                        Jobs of that type, until it will be manually interrupted')
                    ->end()
                    ->arrayNode('jobs')
                        ->useAttributeAsKey('job_name')
                        ->info('Number of seconds of inactivity to qualify jobs of this type as stale.
                        To disable staling jobs for given job type set this option to -1. 
                        Key can be whole job name or a part of it from the beginning of string to any "."')
                        ->example("
                        jobs:
                            bundle_name.processor_name.entity_name.user: X
                            bundle_name.processor_name.entity_name: Y
                            bundle_name.processor_name: Z
                        ")
                        ->prototype('integer')->end()
                    ->end()
                ->end()
            ->end();
        return $tb;
    }
}
