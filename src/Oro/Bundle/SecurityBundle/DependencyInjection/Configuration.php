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

        SettingsBuilder::append(
            $treeBuilder->getRootNode(),
            [
                'symfony_profiler_collection_of_voter_decisions' => [
                    'value' => false
                ],
            ]
        );

        return $treeBuilder;
    }
}
