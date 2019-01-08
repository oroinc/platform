<?php

namespace Oro\Bundle\AssetBundle\DependencyInjection;

use Oro\Bundle\AssetBundle\NodeJsExecutableFinder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $finder = new NodeJsExecutableFinder;
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('oro_asset')
            ->children()
                ->scalarNode('nodejs_path')
                    ->info('Path to NodeJs executable')
                ->end()
                ->scalarNode('npm_path')
                    ->info('Path to NPM executable')
                ->end()
                ->scalarNode('build_timeout')
                    ->defaultValue(120)
                    ->info('Assets build timeout in seconds, null to disable timeout')
                ->end()
                ->scalarNode('npm_install_timeout')
                    ->defaultValue(600)
                    ->info('Npm installation timeout in seconds, null to disable timeout')
                ->end()
            ->end()
            ->validate()
                ->always(
                    function ($value) use ($finder) {
                        if (!isset($value['nodejs_path'])) {
                            $value['nodejs_path'] = (string)$finder->findNodeJs();
                        }
                        if (!isset($value['npm_path'])) {
                            $value['npm_path'] = (string)$finder->findNpm();
                        }

                        return $value;
                    }
                )
            ->end();

        return $treeBuilder;
    }
}
