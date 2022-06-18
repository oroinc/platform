<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines a schema of "Resources/config/oro/features.yml" files.
 */
class FeatureToggleConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'features';

    private ConfigurationExtension $extension;

    public function __construct(ConfigurationExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder(self::ROOT_NODE);
        $root = $builder->getRootNode();

        $children = $root->useAttributeAsKey('name')->prototype('array')->children();

        $this->addFeatureConfiguration($children);
        $this->extension->extendConfigurationTree($children);

        return $builder;
    }

    private function addFeatureConfiguration(NodeBuilder $node): void
    {
        $node
            ->scalarNode('label')
                ->info('A feature title.')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('description')
                ->info('A feature description.')
            ->end()
            ->scalarNode('toggle')
                ->info('A system configuration option key that is used as the feature toggle.')
            ->end()
            ->enumNode('strategy')
                ->info('A strategy that should be used to decide whether the feature is enabled.')
                ->values(['unanimous', 'affirmative', 'consensus'])
            ->end()
            ->booleanNode('allow_if_all_abstain')
                ->info('Defines whether the feature is enabled when all voters abstained from voting.')
            ->end()
            ->booleanNode('allow_if_equal_granted_denied')
                ->info(
                    'Defines whether the feature is enabled when the consensus strategy is used,'
                    . ' and the number of granting and denying voters equals.'
                )
            ->end()
            ->arrayNode('dependencies')
                ->info(
                    'A list of feature names that the feature depends on.'
                    . ' The feature is enabled when all the features from this list are also enabled.'
                )
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('routes')
                ->info('A list of route names.')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('configuration')
                ->info('A list of system configuration group and field names.')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('entities')
                ->info('A list of entity FQCNs.')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('commands')
                ->info(
                    'A list of commands that depend on the feature.'
                    . ' Running these commands is impossible or is not reasonable when the feature is disabled.'
                )
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('mq_topics')
                ->info('A list of message queue topic names.')
                ->prototype('variable')
                ->end()
            ->end();
    }
}
