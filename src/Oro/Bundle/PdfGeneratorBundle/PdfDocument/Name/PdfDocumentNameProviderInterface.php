<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Name;

use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Provides a name for the PDF document based on the source entity.
 */
interface PdfDocumentNameProviderInterface
{
    /**
     * Creates a PDF document name based on the source entity and optional format and locale.
     *
     * @param object $sourceEntity The source entity to create a PDF document name from.
     * @param string|null $format The name format, for example full, short, etc.
     * @param Localization|string|null $locale The name locale.
     *
     * @return string The generated PDF document name.
     */
    public function createPdfDocumentName(
        object $sourceEntity,
        ?string $format = null,
        Localization|string|null $locale = null
    ): string;
}
