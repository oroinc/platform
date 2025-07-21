<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PdfGeneratorBundle\DependencyInjection\Configuration;
use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergPdfEngine;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
    }

    public function testDefaultEngineConfigurationWithGotenberg(): void
    {
        if (!class_exists('Gotenberg\Gotenberg')) {
            self::markTestSkipped('Gotenberg library is not available.');
        }

        $processor = new Processor();
        $configTree = $this->configuration->getConfigTreeBuilder()->buildTree();
        $processedConfig = $processor->process($configTree, []);

        self::assertArrayHasKey('default_engine', $processedConfig);
        self::assertSame(GotenbergPdfEngine::getName(), $processedConfig['default_engine']);
    }

    public function testDefaultEngineConfigurationWithoutGotenberg(): void
    {
        if (class_exists('Gotenberg\Gotenberg')) {
            self::markTestSkipped('Gotenberg library is available.');
        }

        $processor = new Processor();
        $configTree = $this->configuration->getConfigTreeBuilder()->buildTree();
        $processedConfig = $processor->process($configTree, []);

        self::assertArrayHasKey('default_engine', $processedConfig);
        self::assertNull($processedConfig['default_engine']);
    }

    public function testEnginesConfigurationWithGotenberg(): void
    {
        if (!class_exists('Gotenberg\Gotenberg')) {
            self::markTestSkipped('Gotenberg library is not available.');
        }

        $processor = new Processor();
        $configTree = $this->configuration->getConfigTreeBuilder()->buildTree();
        $processedConfig = $processor->process($configTree, []);

        self::assertArrayHasKey('engines', $processedConfig);
        self::assertArrayHasKey('gotenberg', $processedConfig['engines']);
        self::assertArrayHasKey('api_url', $processedConfig['engines']['gotenberg']);
        self::assertEquals(
            '%env(default:oro_pdf_generator.gotenberg_api_url_default:ORO_PDF_GENERATOR_GOTENBERG_API_URL)%',
            $processedConfig['engines']['gotenberg']['api_url']
        );
    }
}
