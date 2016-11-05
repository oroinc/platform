<?php

namespace Oro\Bundle\NavigationBundle\Configuration;

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
            ->arrayNode('navigation_items')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
