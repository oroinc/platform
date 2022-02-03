<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "api_resources" section to "Resources/config/oro/features.yml" configuration file.
 */
class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode('api_resources')
                ->info('A list of entity FQCNs that are available as API resources.')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
