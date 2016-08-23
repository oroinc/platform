<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\ArrayNode;

class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function extendConfigurationTree(ArrayNode $node)
    {
        $node
            ->arrayNode('api')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
