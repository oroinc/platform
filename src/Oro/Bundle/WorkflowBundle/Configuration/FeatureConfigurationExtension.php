<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "workflows" and "processes" sections to "Resources/config/oro/features.yml" configuration file.
 */
class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    const WORKFLOWS_NODE_NAME = 'workflows';
    const PROCESSES_NODE_NAME = 'processes';

    /**
     * {@inheritdoc}
     */
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode(self::WORKFLOWS_NODE_NAME)
                ->info('A list of workflow names.')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode(self::PROCESSES_NODE_NAME)
                ->info('A list of process names.')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
