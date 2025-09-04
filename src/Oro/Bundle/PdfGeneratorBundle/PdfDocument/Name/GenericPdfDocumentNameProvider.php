<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Name;

use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;

/**
 * Provides a name for the PDF document based on the source entity.
 * Makes use of the {@see EntityNameResolver} to generate the name.
 * Ensures that the name is sanitized for use in a filename.
 */
class GenericPdfDocumentNameProvider implements PdfDocumentNameProviderInterface
{
    private ?PreferredLocalizationProviderInterface $preferredLocalizationProvider = null;

    public function __construct(
        private readonly EntityNameResolver $entityNameResolver
    ) {
    }

    public function setPreferredLocalizationProvider(
        ?PreferredLocalizationProviderInterface $preferredLocalizationProvider
    ): void {
        $this->preferredLocalizationProvider = $preferredLocalizationProvider;
    }

    #[\Override]
    public function createPdfDocumentName(
        object $sourceEntity,
        ?string $format = null,
        Localization|string|null $locale = null
    ): string {
        $locale ??= $this->preferredLocalizationProvider?->getPreferredLocalization($sourceEntity);

        $pdfDocumentName = (string) $this->entityNameResolver->getName($sourceEntity, $format, $locale);
        $pdfDocumentName = FilenameSanitizer::sanitizeFilename($pdfDocumentName);

        return mb_strtolower($pdfDocumentName);
    }
}
