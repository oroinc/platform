<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Gotenberg;

use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPresetConfiguratorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Configures PDF options for Gotenberg PDF engine.
 */
class GotenbergPdfOptionsPresetConfigurator implements PdfOptionsPresetConfiguratorInterface
{
    public function __construct(
        private string $gotenbergApiUrl
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->define('gotenberg_api_url')
            ->required()
            ->allowedTypes('string')
            ->default($this->gotenbergApiUrl);
    }

    public function isApplicable(string $pdfEngineName, string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT): bool
    {
        return $pdfEngineName === GotenbergPdfEngine::getName();
    }
}
