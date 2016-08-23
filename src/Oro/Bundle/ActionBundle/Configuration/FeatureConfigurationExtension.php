<?php

namespace Oro\Bundle\ActionBundle\Configuration;

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
            ->arrayNode('operation')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
