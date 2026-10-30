<?php

namespace Oro\Bundle\AssetBundle\DependencyInjection;

use Oro\Bundle\AssetBundle\NodeJsExecutableFinder;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $finder = new NodeJsExecutableFinder();
        $treeBuilder = new TreeBuilder('oro_asset');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('with_babel')
                    ->info('Permanently enable Babel')
                    ->defaultFalse()
                ->end()
                ->scalarNode('nodejs_path')
                    ->info('Path to NodeJs executable')
                ->end()
                ->scalarNode('pnpm_path')
                    ->info('Path to PNPM executable')
                ->end()
                ->scalarNode('build_timeout')
                    ->defaultValue(null)
                    ->info('Assets build timeout in seconds, null to disable timeout')
                ->end()
                ->scalarNode('pnpm_install_timeout')
                    ->defaultValue(null)
                    ->info('PNPM installation timeout in seconds, null to disable timeout')
                ->end()
                ->arrayNode('external_resources')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('link')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->info('The link to the external resource')
                            ->end()
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

                        if (!isset($value['pnpm_path'])) {
                            $value['pnpm_path'] = (string)$finder->findPnpm();
                        }

                        return $value;
                    }
                )
            ->end();

        SettingsBuilder::append(
            $rootNode,
            ['subresource_integrity_enabled' => ['value' => true, 'type' => 'boolean']]
        );

        return $treeBuilder;
    }
}
