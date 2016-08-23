<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

interface ConfigurationExtensionInterface
{
    /**
     * @param NodeBuilder $node
     */
    public function extendConfigurationTree(NodeBuilder $node);
}
