<?php

namespace Oro\Bundle\UIBundle\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "placeholder_items" section to "Resources/config/oro/features.yml" configuration file.
 */
class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    #[\Override]
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode('placeholder_items')
                ->info('A list of placeholder item names.')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
