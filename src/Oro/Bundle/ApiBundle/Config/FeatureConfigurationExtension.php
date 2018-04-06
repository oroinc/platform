<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    const API_RESOURCE_KEY = 'api_resources';

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
