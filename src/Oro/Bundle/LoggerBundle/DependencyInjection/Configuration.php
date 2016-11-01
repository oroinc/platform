<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_logger');

        SettingsBuilder::append($rootNode, [
            'detailed_logs_level' => [
                'value' => null
            ],
            'detailed_logs_end_timestamp' => [
                'value' => null
            ],
        ]);

        return $treeBuilder;
    }
}
