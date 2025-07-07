<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Generator;

use Oro\Bundle\PdfGeneratorBundle\PdfDocument\AbstractPdfDocument;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;

/**
 * Composite PDF document generator that delegates the generation of a PDF file to the first applicable inner generator.
 */
class PdfDocumentGeneratorComposite implements PdfDocumentGeneratorInterface
{
    /**
     * @param iterable<PdfDocumentGeneratorInterface> $innerGenerators
     */
    public function __construct(private readonly iterable $innerGenerators)
    {
    }

    #[\Override]
    public function generatePdfFile(AbstractPdfDocument $pdfDocument): PdfFileInterface
    {
        foreach ($this->innerGenerators as $innerGenerator) {
            if ($innerGenerator->isApplicable($pdfDocument)) {
                return $innerGenerator->generatePdfFile($pdfDocument);
            }
        }

        throw new \InvalidArgumentException('No applicable PDF generator found for the given PDF document.');
    }

    #[\Override]
    public function isApplicable(AbstractPdfDocument $pdfDocument): bool
    {
        foreach ($this->innerGenerators as $innerGenerator) {
            if ($innerGenerator->isApplicable($pdfDocument)) {
                return true;
            }
        }

        return false;
    }
}
