<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroFeatureToggleExtension::ALIAS);

        $rootNode
            ->children()
                ->enumNode('oro_featuretoggle_strategy')
                    ->values([
                        FeatureChecker::STRATEGY_AFFIRMATIVE,
                        FeatureChecker::STRATEGY_CONSENSUS,
                        FeatureChecker::STRATEGY_UNANIMOUS
                    ])
                    ->defaultValue(FeatureChecker::STRATEGY_AFFIRMATIVE)
                ->end()
                ->booleanNode('oro_featuretoggle_allow_if_all_abstain')
                    ->defaultFalse()
                ->end()
                ->booleanNode('oro_featuretoggle_allow_if_equal_granted_denied')
                    ->defaultTrue()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
