<?php

namespace Oro\Bundle\TrackingBundle\DependencyInjection;

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
        $rootNode    = $treeBuilder->root('oro_tracking');

        SettingsBuilder::append(
            $rootNode,
            array(
                'dynamic_tracking_enabled' => ['value' => true],
                'log_rotate_interval'      => ['value' => 60],
                'piwik_host'               => ['value' => null],
                'piwik_token_auth'         => ['value' => null]
            )
        );

        return $treeBuilder;
    }
}
