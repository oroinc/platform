<?php

namespace Oro\Bundle\CronBundle\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "cron_jobs" section to "Resources/config/oro/features.yml" configuration file.
 */
class FeatureConfigurationExtension implements ConfigurationExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function extendConfigurationTree(NodeBuilder $node)
    {
        $node
            ->arrayNode('cron_jobs')
                ->info(
                    'A list of CRON commands that depend on the feature.'
                    . ' These commands are not executed by the cron when the feature is disabled.'
                )
                ->prototype('variable')
                ->end()
            ->end();
    }
}
