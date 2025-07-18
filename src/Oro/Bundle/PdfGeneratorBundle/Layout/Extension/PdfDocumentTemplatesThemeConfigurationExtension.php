<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationExtensionInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "pdf_document" section to the layout theme configuration.
 */
class PdfDocumentTemplatesThemeConfigurationExtension implements ThemeConfigurationExtensionInterface
{
    public const string PDF_DOCUMENT = 'pdf_document';
    public const string CONTENT_TEMPLATE = 'content_template';
    public const string HEADER_TEMPLATE = 'header_template';
    public const string FOOTER_TEMPLATE = 'footer_template';

    #[\Override]
    public function getConfigFileNames(): array
    {
        return [];
    }

    #[\Override]
    public function appendConfig(NodeBuilder $configNode): void
    {
        $configNode
            ->arrayNode(self::PDF_DOCUMENT)
                ->info('Configuration for PDF document templates by document type')
                ->useAttributeAsKey('pdf_document_type')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode(self::CONTENT_TEMPLATE)
                            ->info('Path to the content Twig template')
                            ->cannotBeEmpty()
                            ->isRequired()
                        ->end()
                        ->scalarNode(self::HEADER_TEMPLATE)
                            ->info('Path to the header Twig template')
                        ->end()
                        ->scalarNode(self::FOOTER_TEMPLATE)
                            ->info('Path to the footer Twig template')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
