<?php

namespace Oro\Bundle\CalendarBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_calendar');

        $rootNode
            ->children()
                ->scalarNode('system_calendar_supported')
                // please note that if you want to disable it on already working system
                // you need to take care to create a migration to clean up redundant data
                // in oro_calendar_property table
                ->info('Indicates whether System and/or Public Calendars should be supported or not')
                ->defaultFalse()
            ->end()
        ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'calendar_colors' => [
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
                'event_colors'    => [
                    'value' => [
                        '#5484ED',
                        '#A4BDFC',
                        '#7AE7BF',
                        '#51B749',
                        '#FBD75B',
                        '#FFB878',
                        '#FF887C',
                        '#DC2127',
                        '#DBADFF',
                        '#E1E1E1'
                    ]
                ]
            ]
        );

        return $treeBuilder;
    }
}
