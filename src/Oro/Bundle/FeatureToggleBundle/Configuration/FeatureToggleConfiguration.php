<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines a schema of "Resources/config/oro/features.yml" files.
 */
class FeatureToggleConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'features';

    /** @var ConfigurationExtensionInterface[] */
    private $extensions = [];

    public function addExtension(ConfigurationExtensionInterface $extension)
    {
        $this->extensions[] = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder(self::ROOT_NODE);
        $root = $builder->getRootNode();

        $children = $root->useAttributeAsKey('name')->prototype('array')->children();

        $this->addFeatureConfiguration($children);
        foreach ($this->extensions as $extension) {
            $extension->extendConfigurationTree($children);
        }

        return $builder;
    }

    private function addFeatureConfiguration(NodeBuilder $node)
    {
        $node
            ->scalarNode('toggle')
            ->end()
            ->scalarNode('label')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->scalarNode('description')
            ->end()
            ->arrayNode('dependencies')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('routes')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('configuration')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('entities')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('commands')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('field_configs')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('sidebar_widgets')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('dashboard_widgets')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('cron_jobs')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('api_resources')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('navigation_items')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('operations')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('workflows')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('processes')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('placeholder_items')
                ->prototype('variable')
                ->end()
            ->end()
            ->arrayNode('mq_topics')
                ->prototype('variable')
                ->end()
            ->end()
            ->scalarNode('strategy')
                ->validate()
                    ->ifNotInArray(
                        [
                            FeatureChecker::STRATEGY_AFFIRMATIVE,
                            FeatureChecker::STRATEGY_CONSENSUS,
                            FeatureChecker::STRATEGY_UNANIMOUS
                        ]
                    )
                    ->thenInvalid(
                        'The "strategy" can be "'
                        . FeatureChecker::STRATEGY_AFFIRMATIVE
                        . '", "' . FeatureChecker::STRATEGY_CONSENSUS. '" or "'
                        . FeatureChecker::STRATEGY_UNANIMOUS. '.'
                    )
                ->end()
            ->end()
            ->booleanNode('allow_if_all_abstain')
            ->end()
            ->booleanNode('allow_if_equal_granted_denied')
            ->end();
    }
}
