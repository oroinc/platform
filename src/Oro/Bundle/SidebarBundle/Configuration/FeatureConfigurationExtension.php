<?php

namespace Oro\Bundle\SidebarBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;

class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode('sidebar_widgets')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
