<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "datagrids" section to "Resources/config/oro/features.yml" configuration file.
 */
class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    public const string RESOURCE_TYPE = 'datagrids';

    #[\Override]
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode(self::RESOURCE_TYPE)
                ->info(
                    'A list of datagrid names.'
                    . ' These datagrids are not available when the feature is disabled.'
                )
                ->prototype('variable')
                ->end()
            ->end();
    }
}
