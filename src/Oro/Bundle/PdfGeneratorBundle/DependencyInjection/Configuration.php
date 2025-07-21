<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\DependencyInjection;

use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergPdfEngine;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const string ROOT_NODE = 'oro_pdf_generator';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(static::ROOT_NODE);

        $rootNode = $treeBuilder->getRootNode();

        $gotenbergExists = class_exists('Gotenberg\Gotenberg');
        if ($gotenbergExists) {
            $defaultEngine = GotenbergPdfEngine::getName();
        }

        $rootNode
            ->children()
                ->scalarNode('default_engine')
                    ->cannotBeEmpty()
                    ->defaultValue($defaultEngine ?? null)
                    ->info('Name of the default PDF engine.')
                ->end()
            ->end();

        $enginesNode = $rootNode
            ->children()
                ->arrayNode('engines')
                    ->addDefaultsIfNotSet();

        if ($gotenbergExists) {
            $this->appendGotenbergEngineConfigNode($enginesNode);
        }

        return $treeBuilder;
    }

    private function appendGotenbergEngineConfigNode(NodeDefinition $enginesNode): void
    {
        $enginesNode
            ->children()
                ->arrayNode('gotenberg')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('api_url')
                            ->cannotBeEmpty()
                            ->defaultValue(
                                sprintf(
                                    '%%env(default:%s:%s)%%',
                                    // Fallback container parameter.
                                    'oro_pdf_generator.gotenberg_api_url_default',
                                    // Environment variable name.
                                    'ORO_PDF_GENERATOR_GOTENBERG_API_URL'
                                )
                            )
                            ->info('Gotenberg API URL.')
                            ->example(['https://demo.gotenberg.dev', 'http://127.0.0.1:3000'])
                        ->end()
                    ->end()
                ->end();
    }
}
