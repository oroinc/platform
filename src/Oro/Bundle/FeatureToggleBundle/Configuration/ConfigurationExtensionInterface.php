<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The interface for feature configuration extensions.
 */
interface ConfigurationExtensionInterface
{
    public function extendConfigurationTree(NodeBuilder $node);
}
