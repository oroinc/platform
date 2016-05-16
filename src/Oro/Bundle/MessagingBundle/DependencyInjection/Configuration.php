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
            ->arrayNode('transport')->children()
                ->arrayNode('amqp')->children()
                    ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('port')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('pass')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('vhost')->isRequired()->cannotBeEmpty()->end()
        ;

        return $tb;
    }
}

