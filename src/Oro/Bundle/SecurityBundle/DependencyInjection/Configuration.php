<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Cookie;

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
                    ->enumNode('cookie_samesite')
                        ->values([null, Cookie::SAMESITE_LAX, Cookie::SAMESITE_STRICT, Cookie::SAMESITE_NONE])
                        ->defaultNull()
                        ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
