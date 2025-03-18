<?php

namespace Oro\Bundle\TagBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_tag');
        $rootNode    = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'taxonomy_colors' => [
                    'value' => [
                        '#A57261',
                        '#CD7B6C',
                        '#A92F1F',
                        '#CD5642',
                        '#DE703F',
                        '#E09B45',
                        '#80C4A6',
                        '#368360',
                        '#96C27C',
                        '#CADEAE',
                        '#E3D47D',
                        '#D1C15C',
                        '#ACD5C4',
                        '#9EC8CC',
                        '#8EADC7',
                        '#5978A9',
                        '#AA9FC2',
                        '#C2C2C2',
                        '#CABDBF',
                        '#CCA6AC',
                        '#AF6C82',
                        '#895E95',
                        '#7D6D94'
                    ]
                ],
            ]
        );

        return $treeBuilder;
    }
}
