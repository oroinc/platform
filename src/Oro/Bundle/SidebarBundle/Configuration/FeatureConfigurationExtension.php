<?php

namespace Oro\Bundle\SidebarBundle\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "sidebar_widgets" section to "Resources/config/oro/features.yml" configuration file.
 */
class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    #[\Override]
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode('sidebar_widgets')
                ->info('A list of sidebar widget names.')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
