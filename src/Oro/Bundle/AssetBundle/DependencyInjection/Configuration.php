<?php

namespace Oro\Bundle\AssetBundle\DependencyInjection;

use Oro\Bundle\AssetBundle\NodeJsExecutableFinder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $finder = new NodeJsExecutableFinder;
        $treeBuilder = new TreeBuilder('oro_asset');
        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('with_babel')
                    ->info('Permanently enable Babel')
                    ->defaultFalse()
                ->end()
                ->scalarNode('nodejs_path')
                    ->info('Path to NodeJs executable')
                ->end()
                ->scalarNode('npm_path')
                    ->info('Path to NPM executable')
                ->end()
                ->scalarNode('build_timeout')
                    ->defaultValue(null)
                    ->info('Assets build timeout in seconds, null to disable timeout')
                ->end()
                ->scalarNode('npm_install_timeout')
                    ->defaultValue(null)
                    ->info('Npm installation timeout in seconds, null to disable timeout')
                ->end()
                ->arrayNode('webpack_dev_server')
                    ->info('Webpack Dev Server configuration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('enable_hmr')
                            ->info(
                                'Enable Webpack Hot Module Replacement. '.
                                'To activate HMR run `oro:assets:build --hot`'
                            )
                            ->defaultValue('%kernel.debug%')
                        ->end()
                        ->scalarNode('host')
                            ->info('By Default `localhost` is used')
                            ->defaultValue('localhost')
                        ->end()
                        ->scalarNode('port')
                            ->defaultValue(8081)
                            ->beforeNormalization()
                                ->ifString()->then(
                                    function ($v) {
                                        if (is_numeric($v)) {
                                            $v = (int)$v;
                                        }

                                        return $v;
                                    }
                                )
                            ->end()
                            ->validate()
                                ->always(
                                    function ($v) {
                                        if (!is_int($v) || $v < 1 || $v > 65535) {
                                            throw new \InvalidArgumentException(
                                                'Expected an integer between 1 and 65535.'
                                            );
                                        }

                                        return $v;
                                    }
                                )
                            ->end()
                        ->end()
                        ->booleanNode('https')
                            ->info('By default dev-server will be served over HTTP. ' .
                                'It can optionally be served over HTTP/2 with HTTPS')
                            ->defaultFalse()
                        ->end()
                    ->end()
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
