<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "api_resources" section to "Resources/config/oro/features.yml" configuration file.
 */
class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    public const API_RESOURCE_KEY = 'api_resources';

    /**
     * {@inheritdoc}
     */
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode(self::API_RESOURCE_KEY)
                ->prototype('variable')
                ->end()
            ->end();
    }
}
