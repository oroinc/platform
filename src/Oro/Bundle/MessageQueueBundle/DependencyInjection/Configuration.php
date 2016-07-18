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
                ->scalarNode('router_processor')->defaultNull()->end()
                ->scalarNode('router_destination')->defaultValue('default')->cannotBeEmpty()->end()
                ->scalarNode('default_destination')->defaultValue('default')->cannotBeEmpty()->end()
            ->end()->end()
        ;

        return $tb;
    }
}
