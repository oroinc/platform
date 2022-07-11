<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_featuretoggle';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->enumNode('strategy')
                    ->info('A strategy that should be used to decide whether a feature is enabled.')
                    ->values(['unanimous', 'affirmative', 'consensus'])
                    ->defaultValue('unanimous')
                ->end()
                ->booleanNode('allow_if_all_abstain')
                    ->info('Defines whether a feature is enabled when all voters abstained from voting.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('allow_if_equal_granted_denied')
                    ->info(
                        'Defines whether a feature is enabled when the consensus strategy is used,'
                        . ' and the number of granting and denying voters equals.'
                    )
                    ->defaultTrue()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
