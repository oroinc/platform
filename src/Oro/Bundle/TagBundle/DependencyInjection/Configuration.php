<?php

namespace Oro\Bundle\TagBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_tag');

        SettingsBuilder::append(
            $rootNode,
            [
                'taxonomy_colors' => [
                    'value' => [
                        '#AC725E',
                        '#D06B64',
                        '#F83A22',
                        '#FA573C',
                        '#FF7537',
                        '#FFAD46',
                        '#42D692',
                        '#16A765',
                        '#7BD148',
                        '#B3DC6C',
                        '#FBE983',
                        '#FAD165',
                        '#92E1C0',
                        '#9FE1E7',
                        '#9FC6E7',
                        '#4986E7',
                        '#9A9CFF',
                        '#B99AFF',
                        '#C2C2C2',
                        '#CABDBF',
                        '#CCA6AC',
                        '#F691B2',
                        '#CD74E6',
                        '#A47AE2'
                    ]
                ],
            ]
        );

        return $treeBuilder;
    }
}
