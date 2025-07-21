<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfOptionsPreset;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Configures PDF options taking into account PDF engine and PDF options preset.
 */
interface PdfOptionsPresetConfiguratorInterface
{
    /**
     * Configures the options for the PDF options preset.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * @param string $pdfEngineName PDF engine name as defined in {@see PdfEngineInterface::getName}
     * @param string $pdfOptionsPreset PDF options preset name (e.g., default, default_a4, etc.).
     *
     * @return bool
     */
    public function isApplicable(string $pdfEngineName, string $pdfOptionsPreset = PdfOptionsPreset::DEFAULT): bool;
}
