<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Gotenberg;

use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergPdfEngine;
use Oro\Bundle\PdfGeneratorBundle\Gotenberg\GotenbergPdfOptionsPresetConfigurator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GotenbergPdfOptionsPresetConfiguratorTest extends TestCase
{
    private GotenbergPdfOptionsPresetConfigurator $configurator;

    private string $gotenbergApiUrl;

    protected function setUp(): void
    {
        $this->gotenbergApiUrl = 'http://localhost:3000';
        $this->configurator = new GotenbergPdfOptionsPresetConfigurator($this->gotenbergApiUrl);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();

        $this->configurator->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        self::assertSame($this->gotenbergApiUrl, $resolvedOptions['gotenberg_api_url']);
    }

    public function testIsApplicableWithMatchingEngine(): void
    {
        self::assertTrue($this->configurator->isApplicable(GotenbergPdfEngine::getName()));
    }

    public function testIsApplicableWithNonMatchingEngine(): void
    {
        self::assertFalse($this->configurator->isApplicable('other_engine'));
    }
}
