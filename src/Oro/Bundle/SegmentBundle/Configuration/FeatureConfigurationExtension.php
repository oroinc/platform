<?php

namespace Oro\Bundle\SegmentBundle\Configuration;

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
            ->arrayNode('segments')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
