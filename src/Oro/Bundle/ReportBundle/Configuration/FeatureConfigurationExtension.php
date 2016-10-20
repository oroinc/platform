<?php

namespace Oro\Bundle\ReportBundle\Configuration;

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
            ->arrayNode('reports')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
