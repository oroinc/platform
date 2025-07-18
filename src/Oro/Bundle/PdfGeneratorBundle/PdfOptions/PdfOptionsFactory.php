<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfOptions;

use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPreset;
use Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset\PdfOptionsPresetConfiguratorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Creates {@see PdfOptions} and delegates the configuration to PDF options preset configurators.
 */
class PdfOptionsFactory implements PdfOptionsFactoryInterface
{
    /**
     * @param iterable<PdfOptionsPresetConfiguratorInterface> $pdfOptionsPresetConfigurators
     */
    public function __construct(private iterable $pdfOptionsPresetConfigurators)
    {
    }

    #[\Override]
    public function createPdfOptions(
        string $pdfEngineName,
        string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT
    ): PdfOptionsInterface {
        $optionsResolver = new OptionsResolver();

        foreach ($this->pdfOptionsPresetConfigurators as $pdfOptionsPresetConfigurator) {
            if ($pdfOptionsPresetConfigurator->isApplicable($pdfEngineName, $pdfOptionsPreset)) {
                $pdfOptionsPresetConfigurator->configureOptions($optionsResolver);
            }
        }

        return new PdfOptions([], $optionsResolver, $pdfOptionsPreset);
    }
}
