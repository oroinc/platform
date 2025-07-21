<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\PdfGeneratorBundle\Layout\Extension\PdfDocumentTemplatesThemeConfigurationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class PdfDocumentTemplatesThemeConfigurationExtensionTest extends TestCase
{
    private TreeBuilder $treeBuilder;

    private PdfDocumentTemplatesThemeConfigurationExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new PdfDocumentTemplatesThemeConfigurationExtension();
        $this->treeBuilder = new TreeBuilder('config');
    }

    public function testWithEmptyConfig(): void
    {
        $nodeBuilder = $this->treeBuilder->getRootNode()->children();

        $this->extension->appendConfig($nodeBuilder);

        $configTree = $this->treeBuilder->buildTree();
        $config = $configTree->finalize([]);

        self::assertArrayHasKey('pdf_document', $config);
    }

    public function testWithContentTemplate(): void
    {
        $nodeBuilder = $this->treeBuilder->getRootNode()->children();

        $this->extension->appendConfig($nodeBuilder);

        $configTree = $this->treeBuilder->buildTree();
        $config = $configTree->finalize([
            'pdf_document' => [
                'sample_type' => [
                    'content_template' => '@OroPdfGenerator/PdfDocument/content.html.twig',
                ],
            ],
        ]);

        self::assertArrayHasKey('pdf_document', $config);
        self::assertArrayHasKey('sample_type', $config['pdf_document']);
        self::assertSame(
            '@OroPdfGenerator/PdfDocument/content.html.twig',
            $config['pdf_document']['sample_type']['content_template'],
        );
    }

    public function testWithoutContentTemplate(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The child config "content_template" under "config.pdf_document.sample_type" must be configured: '
            . 'Path to the content Twig template'
        );

        $nodeBuilder = $this->treeBuilder->getRootNode()->children();

        $this->extension->appendConfig($nodeBuilder);

        $configTree = $this->treeBuilder->buildTree();
        $configTree->finalize([
            'pdf_document' => [
                'sample_type' => [],
            ],
        ]);
    }

    public function testWithAllTemplates(): void
    {
        $nodeBuilder = $this->treeBuilder->getRootNode()->children();

        $this->extension->appendConfig($nodeBuilder);

        $configTree = $this->treeBuilder->buildTree();
        $config = $configTree->finalize([
            'pdf_document' => [
                'sample_type' => [
                    'content_template' => '@OroPdfGenerator/PdfDocument/content.html.twig',
                    'header_template' => '@OroPdfGenerator/PdfDocument/header.html.twig',
                    'footer_template' => '@OroPdfGenerator/PdfDocument/footer.html.twig',
                ],
            ],
        ]);

        self::assertArrayHasKey('pdf_document', $config);
        self::assertArrayHasKey('sample_type', $config['pdf_document']);
        self::assertSame(
            '@OroPdfGenerator/PdfDocument/content.html.twig',
            $config['pdf_document']['sample_type']['content_template']
        );
        self::assertSame(
            '@OroPdfGenerator/PdfDocument/header.html.twig',
            $config['pdf_document']['sample_type']['header_template']
        );
        self::assertSame(
            '@OroPdfGenerator/PdfDocument/footer.html.twig',
            $config['pdf_document']['sample_type']['footer_template']
        );
    }

    public function testWithMultiplePdfDocumentTypes(): void
    {
        $nodeBuilder = $this->treeBuilder->getRootNode()->children();

        $this->extension->appendConfig($nodeBuilder);

        $configTree = $this->treeBuilder->buildTree();
        $config = $configTree->finalize([
            'pdf_document' => [
                'type_one' => [
                    'content_template' => '@OroPdfGenerator/PdfDocument/type_one_content.html.twig',
                    'header_template' => '@OroPdfGenerator/PdfDocument/type_one_header.html.twig',
                ],
                'type_two' => [
                    'content_template' => '@OroPdfGenerator/PdfDocument/type_two_content.html.twig',
                    'footer_template' => '@OroPdfGenerator/PdfDocument/type_two_footer.html.twig',
                ],
            ],
        ]);

        self::assertArrayHasKey('pdf_document', $config, 'The "pdf_document" node should exist in the configuration.');
        self::assertArrayHasKey(
            'type_one',
            $config['pdf_document'],
            'The "type_one" node should exist under "pdf_document".'
        );
        self::assertArrayHasKey(
            'type_two',
            $config['pdf_document'],
            'The "type_two" node should exist under "pdf_document".'
        );

        self::assertSame(
            '@OroPdfGenerator/PdfDocument/type_one_content.html.twig',
            $config['pdf_document']['type_one']['content_template'],
            'The "content_template" for "type_one" should match the expected template path.'
        );
        self::assertSame(
            '@OroPdfGenerator/PdfDocument/type_one_header.html.twig',
            $config['pdf_document']['type_one']['header_template'],
            'The "header_template" for "type_one" should match the expected template path.'
        );
        self::assertSame(
            '@OroPdfGenerator/PdfDocument/type_two_content.html.twig',
            $config['pdf_document']['type_two']['content_template'],
            'The "content_template" for "type_two" should match the expected template path.'
        );
        self::assertSame(
            '@OroPdfGenerator/PdfDocument/type_two_footer.html.twig',
            $config['pdf_document']['type_two']['footer_template'],
            'The "footer_template" for "type_two" should match the expected template path.'
        );
    }
}
