<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_security');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'symfony_profiler_collection_of_voter_decisions' => [
                    'value' => false
                ],
            ]
        );

        $rootNode->children()
            ->arrayNode('csrf_cookie')
                ->addDefaultsIfNotSet()
                ->children()
                    ->enumNode('cookie_secure')->values([true, false, 'auto'])->defaultValue('auto')->end()
                    ->booleanNode('cookie_httponly')->defaultFalse()->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
